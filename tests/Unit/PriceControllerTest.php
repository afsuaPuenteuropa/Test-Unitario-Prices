<?php

use App\Http\Controllers\PriceController;

beforeEach(function () {
    $this->controller = new PriceController;
});

/*
|--------------------------------------------------------------------------
| Currency conversion
|--------------------------------------------------------------------------
*/

test('converts euros to dollars using the default rate', function () {
    expect($this->controller->convertCurrency(100, 'EUR', 'USD'))
        ->toBe(110.0);
});

test('converts dollars to euros using the default rate', function () {
    expect($this->controller->convertCurrency(110, 'USD', 'EUR'))
        ->toBe(100.0);
});

test('converts euros to dollars using a custom rate', function () {
    expect($this->controller->convertCurrency(100, 'EUR', 'USD', 1.25))
        ->toBe(125.0);
});

test('converts dollars to euros using a custom rate in reverse', function () {
    expect($this->controller->convertCurrency(125, 'USD', 'EUR', 1.25))
        ->toBe(100.0);
});

test('accepts lowercase currency codes', function () {
    expect($this->controller->convertCurrency(100, 'eur', 'usd'))
        ->toBe(110.0);
});

test('returns the same amount when currencies match', function () {
    expect($this->controller->convertCurrency(42.5, 'EUR', 'EUR'))
        ->toBe(42.5)
        ->and($this->controller->convertCurrency(42.5, 'USD', 'USD'))
        ->toBe(42.5);
});

test('rounds converted amounts to two decimals', function () {
    expect($this->controller->convertCurrency(10, 'EUR', 'USD', 1.099))
        ->toBe(10.99)
        ->and($this->controller->convertCurrency(10, 'USD', 'EUR', 1.099))
        ->toBe(9.10)
        ->and($this->controller->convertCurrency(123.456, 'EUR', 'USD'))
        ->toBe(135.80);
});

test('converts a zero amount to zero', function () {
    expect($this->controller->convertCurrency(0, 'EUR', 'USD'))->toBe(0.0);
});

test('throws an exception when converting a negative amount', function () {
    expect(fn () => $this->controller->convertCurrency(-1, 'EUR', 'USD'))
        ->toThrow(InvalidArgumentException::class, 'The amount cannot be negative.');
});

test('throws an exception for unsupported currencies', function () {
    expect(fn () => $this->controller->convertCurrency(100, 'EUR', 'GBP'))
        ->toThrow(InvalidArgumentException::class, 'Only EUR and USD currencies are supported.');
});

test('throws an exception when the exchange rate is zero or negative', function () {
    expect(fn () => $this->controller->convertCurrency(100, 'EUR', 'USD', 0))
        ->toThrow(InvalidArgumentException::class, 'The exchange rate must be greater than zero.')
        ->and(fn () => $this->controller->convertCurrency(100, 'EUR', 'USD', -1.5))
        ->toThrow(InvalidArgumentException::class, 'The exchange rate must be greater than zero.');
});

/*
|--------------------------------------------------------------------------
| Discounts
|--------------------------------------------------------------------------
*/

test('calculates the final price after a percentage discount', function () {
    expect($this->controller->calculateDiscount(100, 20))->toBe(80.0);
});

test('calculates a discount on a decimal price', function () {
    expect($this->controller->calculateDiscount(250.50, 10))->toBe(225.45);
});

test('returns the original price when the discount is zero', function () {
    expect($this->controller->calculateDiscount(99.99, 0))->toBe(99.99);
});

test('returns zero when the discount is one hundred percent', function () {
    expect($this->controller->calculateDiscount(49.95, 100))->toBe(0.0);
});

test('rounds the final price to two decimals', function () {
    expect($this->controller->calculateDiscount(99.99, 15))->toBe(84.99)
        ->and($this->controller->calculateDiscount(19.99, 33))->toBe(13.39);
});

test('calculates the amount saved by the discount', function () {
    expect($this->controller->discountAmount(100, 20))->toBe(20.0)
        ->and($this->controller->discountAmount(250.50, 10))->toBe(25.05)
        ->and($this->controller->discountAmount(99.99, 15))->toBe(15.0);
});

test('final price plus discount equals the original price', function () {
    $price = 149.99;
    $percentage = 17.5;

    $saved = $this->controller->discountAmount($price, $percentage);
    $final = $this->controller->calculateDiscount($price, $percentage);

    expect(round($final + $saved, 2))->toBe($price);
});

test('throws an exception when the price is negative', function () {
    expect(fn () => $this->controller->calculateDiscount(-10, 20))
        ->toThrow(InvalidArgumentException::class, 'The price cannot be negative.');
});

test('throws an exception when the discount is below zero', function () {
    expect(fn () => $this->controller->calculateDiscount(100, -5))
        ->toThrow(InvalidArgumentException::class, 'The discount percentage must be between 0 and 100.');
});

test('throws an exception when the discount is above one hundred percent', function () {
    expect(fn () => $this->controller->calculateDiscount(100, 101))
        ->toThrow(InvalidArgumentException::class, 'The discount percentage must be between 0 and 100.');
});
