<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Product Stock Alert & Filter', function () {
    test('product isLowStock respects warning quantity configuration', function () {
        config(['settings.warning_quantity' => 15]);

        $productLow = Product::factory()->create(['quantity' => 12]);
        $productNormal = Product::factory()->create(['quantity' => 20]);

        expect($productLow->isLowStock())->toBeTrue();
        expect($productNormal->isLowStock())->toBeFalse();
    });

    test('product scopeLowStock filters products below threshold', function () {
        config(['settings.warning_quantity' => 10]);

        $p1 = Product::factory()->create(['quantity' => 5]);
        $p2 = Product::factory()->create(['quantity' => 15]);

        $lowStockList = Product::lowStock()->get();

        expect($lowStockList->contains($p1))->toBeTrue();
        expect($lowStockList->contains($p2))->toBeFalse();
    });

    test('products index can filter by low stock and out of stock', function () {
        $pLow = Product::factory()->create(['quantity' => 4]);
        $pZero = Product::factory()->create(['quantity' => 0]);
        $pGood = Product::factory()->create(['quantity' => 50]);

        $responseLow = $this->get(route('products.index', ['stock_status' => 'low_stock']));
        $responseLow->assertOk()
            ->assertViewHas('products', function ($products) use ($pLow, $pGood) {
                return $products->contains($pLow) && !$products->contains($pGood);
            });

        $responseZero = $this->get(route('products.index', ['stock_status' => 'out_of_stock']));
        $responseZero->assertOk()
            ->assertViewHas('products', function ($products) use ($pZero, $pGood) {
                return $products->contains($pZero) && !$products->contains($pGood);
            });
    });
});
