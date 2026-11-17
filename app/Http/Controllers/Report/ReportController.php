<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display sales and inventory analytics reports.
     */
    public function index(Request $request)
    {
        $preset = $request->input('preset', 'this_month');
        $dates = $this->resolveDateRange($request, $preset);
        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];

        // Base query for orders within range
        $ordersQuery = Order::query()
            ->with(['items.product', 'payments', 'customer'])
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $orders = $ordersQuery->get();

        // High-level metrics
        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum(fn($o) => $o->total());
        $totalReceived = $orders->sum(fn($o) => $o->receivedAmount());
        $totalItemsSold = $orders->sum(fn($o) => $o->items->sum('quantity'));
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        // Estimated Gross Profit (price - purchase_price * quantity)
        $grossProfit = 0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $cost = (float) ($item->product->purchase_price ?? 0) * $item->quantity;
                $grossProfit += ($item->price - $cost);
            }
        }

        // Daily breakdown within range
        $dailyData = [];
        foreach ($orders as $order) {
            $day = $order->created_at->format('Y-m-d');
            if (!isset($dailyData[$day])) {
                $dailyData[$day] = [
                    'date' => $day,
                    'orders_count' => 0,
                    'items_count' => 0,
                    'revenue' => 0,
                    'received' => 0,
                ];
            }
            $dailyData[$day]['orders_count']++;
            $dailyData[$day]['items_count'] += $order->items->sum('quantity');
            $dailyData[$day]['revenue'] += $order->total();
            $dailyData[$day]['received'] += $order->receivedAmount();
        }
        krsort($dailyData);

        // Top selling products in this range
        $orderIds = $orders->pluck('id');
        $topProducts = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price) as total_amount')
            )
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Low stock products alert
        $warningLimit = (int) (config('settings.warning_quantity') ?: 10);
        $lowStockProducts = Product::query()
            ->where('quantity', '<', $warningLimit)
            ->orderBy('quantity')
            ->limit(15)
            ->get();

        return view('reports.index', [
            'preset' => $preset,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalReceived' => $totalReceived,
            'totalItemsSold' => $totalItemsSold,
            'avgOrderValue' => $avgOrderValue,
            'grossProfit' => $grossProfit,
            'dailyData' => $dailyData,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'warningLimit' => $warningLimit,
        ]);
    }

    /**
     * Export sales report data to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $preset = $request->input('preset', 'this_month');
        $dates = $this->resolveDateRange($request, $preset);
        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];

        $orders = Order::query()
            ->with(['items.product', 'payments', 'customer', 'user'])
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->latest()
            ->get();

        $filename = 'sales-report-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'Order ID',
                'Date',
                'Customer',
                'Cashier',
                'Items Count',
                'Total Amount',
                'Received Amount',
                'Balance',
                'Status'
            ]);

            foreach ($orders as $order) {
                $total = $order->total();
                $received = $order->receivedAmount();
                $balance = $total - $received;
                $status = $balance <= 0 ? 'Paid' : ($received > 0 ? 'Partial' : 'Unpaid');

                fputcsv($handle, [
                    $order->id,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->getCustomerName(),
                    $order->user->name ?? ($order->user->first_name ?? 'N/A'),
                    $order->items->sum('quantity'),
                    number_format($total, 2, '.', ''),
                    number_format($received, 2, '.', ''),
                    number_format(max(0, $balance), 2, '.', ''),
                    $status
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Resolve date range according to preset or custom input.
     *
     * @return array{start_date: Carbon, end_date: Carbon}
     */
    private function resolveDateRange(Request $request, string $preset): array
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return [
                'start_date' => Carbon::parse($request->input('start_date')),
                'end_date' => Carbon::parse($request->input('end_date')),
            ];
        }

        return match ($preset) {
            'today' => [
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today(),
            ],
            'last_7_days' => [
                'start_date' => Carbon::today()->subDays(6),
                'end_date' => Carbon::today(),
            ],
            'last_month' => [
                'start_date' => Carbon::now()->subMonth()->startOfMonth(),
                'end_date' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            'all_time' => [
                'start_date' => Carbon::createFromTimestamp(0),
                'end_date' => Carbon::now(),
            ],
            default => [ // this_month
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => Carbon::now(),
            ],
        };
    }
}
