<?php

if (!function_exists('activeSegment')) {
    function activeSegment($name, $segment = 2, $class = 'active')
    {
        return request()->segment($segment) == $name ? $class : '';
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return (string) config('settings.currency_symbol', '$');
    }
}

if (!function_exists('currency_format')) {
    function currency_format($amount, int $decimals = 2): string
    {
        $symbol = currency_symbol();
        $placement = config('settings.currency_placement', 'left');
        $formatted = number_format((float) $amount, $decimals);

        return $placement === 'right' ? "{$formatted} {$symbol}" : "{$symbol} {$formatted}";
    }
}

