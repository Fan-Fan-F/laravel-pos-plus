<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Multi Currency & Formatting Settings', function () {
    test('authenticated user can update currency symbol and position settings', function () {
        $response = $this->post(route('settings.store'), [
            'app_name' => 'Custom POS Store',
            'app_description' => 'Great Store',
            'currency_symbol' => '€',
            'currency_placement' => 'right',
            'warning_quantity' => 15,
        ]);

        $response->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'currency_symbol', 'value' => '€']);
        $this->assertDatabaseHas('settings', ['key' => 'currency_placement', 'value' => 'right']);
        $this->assertDatabaseHas('settings', ['key' => 'warning_quantity', 'value' => '15']);
    });

    test('currency_format helper formats amounts according to configuration', function () {
        config(['settings.currency_symbol' => '$', 'settings.currency_placement' => 'left']);
        expect(currency_format(100.5))->toBe('$ 100.50');

        config(['settings.currency_symbol' => '€', 'settings.currency_placement' => 'right']);
        expect(currency_format(100.5))->toBe('100.50 €');

        config(['settings.currency_symbol' => '៛', 'settings.currency_placement' => 'right']);
        expect(currency_format(4000, 0))->toBe('4,000 ៛');
    });
});
