<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Sales & Inventory Reports', function () {
    test('authenticated user can view reports index page with KPI metrics', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Report Widget', 'price' => 50, 'purchase_price' => 30]);

        $order = Order::factory()->create(['customer_id' => $customer->id, 'user_id' => $this->user->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'price' => 200,
        ]);
        $order->payments()->create([
            'amount' => 200,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('reports.index'));

        $response->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHas([
                'totalOrders',
                'totalRevenue',
                'totalReceived',
                'totalItemsSold',
                'avgOrderValue',
                'grossProfit',
                'dailyData',
                'topProducts',
                'lowStockProducts',
            ]);
    });

    test('authenticated user can export sales report as csv', function () {
        $product = Product::factory()->create(['name' => 'Export Item', 'price' => 25]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50,
        ]);

        $response = $this->get(route('reports.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    });

    test('reports filter by custom date ranges correctly', function () {
        $product = Product::factory()->create(['price' => 100]);

        // Order 15 days ago
        $pastOrder = Order::factory()->create(['created_at' => now()->subDays(15)]);
        $pastOrder->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        // Order today
        $todayOrder = Order::factory()->create(['created_at' => now()]);
        $todayOrder->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        $response = $this->get(route('reports.index', [
            'start_date' => now()->subDays(2)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertViewHas('totalOrders', 1);
    });

    test('guests cannot access reports', function () {
        auth()->logout();

        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('reports.export'))->assertRedirect(route('login'));
    });
});
