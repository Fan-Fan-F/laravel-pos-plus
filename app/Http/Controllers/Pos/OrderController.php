<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderStoreRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $filteredQuery = Order::query()
            ->with(['items.product', 'payments', 'customer'])
            ->when($request->input('start_date'), function ($query, $startDate): void {
                $query->where('created_at', '>=', $startDate);
            })
            ->when($request->input('end_date'), function ($query, string $endDate): void {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            });

        // Compute totals across all filtered orders
        $allOrders = (clone $filteredQuery)->get();
        $total = $allOrders->sum(fn($order) => $order->total());
        $receivedAmount = $allOrders->sum(fn($order) => $order->receivedAmount());

        $orders = $filteredQuery
            ->latest()
            ->paginate(10);

        return view('orders.index', ['orders' => $orders, 'total' => $total, 'receivedAmount' => $receivedAmount]);
    }

    /**
     * Generate 80mm thermal receipt PDF for the order.
     */
    public function receipt(Order $order)
    {
        $order->load(['customer', 'user', 'items.product', 'payments']);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('orders.receipt', ['order' => $order]);
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width

        return $pdf->stream("order-receipt-{$order->id}.pdf");
    }

    /**
     * Display printable HTML thermal receipt with auto-print.
     */
    public function print(Order $order)
    {
        $order->load(['customer', 'user', 'items.product', 'payments']);

        return view('orders.print', ['order' => $order]);
    }

    public function store(OrderStoreRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $order = DB::transaction(function () use ($request) {
                // Create order
                $order = Order::create([
                    'customer_id' => $request->customer_id,
                    'user_id' => $request->user()->id,
                ]);

                // Get cart items
                $cartItems = $request->user()->cart()->get();

                if ($cartItems->isEmpty()) {
                    throw new \Exception(__('cart.empty'));
                }

                // Create order items and update product quantities
                foreach ($cartItems as $item) {
                    $this->reduceProductStock($item);
                    $this->createOrderItem($order, $item);
                }

                // Clear cart
                $request->user()->cart()->detach();

                // Create payment
                $order->payments()->create([
                    'amount' => $request->amount,
                    'user_id' => $request->user()->id,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => __('order.created_successfully'),
                'order_id' => $order->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function partialPayment(Request $request)
    {
        $order = Order::findOrFail($request->input('order_id'));
        $remainingAmount = $order->total() - $order->receivedAmount();

        if ($request->input('amount') > $remainingAmount) {
            return redirect()->route('orders.index')
                ->withErrors(__('order.amount_exceeds_balance'));
        }

        DB::transaction(function () use ($order, $request): void {
            $order->payments()->create([
                'amount' => $request->amount,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('orders.index')
            ->with('success', __('order.partial_payment_success', [
                'amount' => config('settings.currency_symbol') . number_format($request->amount, 2)
            ]));
    }

    /**
     * Create an order item from cart item.
     */
    private function createOrderItem(Order $order, $item): void
    {
        $order->items()->create([
            'price' => $item->price * $item->pivot->quantity,
            'quantity' => $item->pivot->quantity,
            'product_id' => $item->id,
        ]);
    }

    /**
     * Reduce product stock based on cart quantity.
     *
     * The stock check and decrement run as a single conditional UPDATE, so
     * concurrent checkouts cannot sell more than the current stock. Throwing
     * here rolls back the surrounding checkout transaction.
     */
    private function reduceProductStock($item): void
    {
        $updated = Product::whereKey($item->id)
            ->where('quantity', '>=', $item->pivot->quantity)
            ->decrement('quantity', $item->pivot->quantity);

        if ($updated === 0) {
            $available = (int) Product::whereKey($item->id)->value('quantity');

            throw new \Exception(__('cart.available', ['quantity' => $available]));
        }
    }
}
