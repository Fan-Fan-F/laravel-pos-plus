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

describe('Order Receipt & Printing', function () {
    test('authenticated user can view order thermal print receipt view', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Coffee', 'price' => 15.50]);
        $order = Order::factory()->create(['customer_id' => $customer->id, 'user_id' => $this->user->id]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 31.00,
        ]);
        $order->payments()->create([
            'amount' => 31.00,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('orders.print', $order));

        $response->assertOk()
            ->assertViewIs('orders.print')
            ->assertViewHas('order')
            ->assertSee('Test Coffee')
            ->assertSee('SALE RECEIPT')
            ->assertSee('#' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT));
    });

    test('authenticated user can stream or download order pdf receipt', function () {
        $product = Product::factory()->create(['name' => 'Burger', 'price' => 20]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 20,
        ]);
        $order->payments()->create([
            'amount' => 20,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('orders.receipt', $order));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    });

    test('guests cannot view order receipts', function () {
        auth()->logout();
        $order = Order::factory()->create();

        $this->get(route('orders.print', $order))->assertRedirect(route('login'));
        $this->get(route('orders.receipt', $order))->assertRedirect(route('login'));
    });
});
