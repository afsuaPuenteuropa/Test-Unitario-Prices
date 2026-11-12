<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PriceController extends Controller
{
    /**
     * Default exchange rate: 1 EUR = 1.10 USD.
     */
    public const DEFAULT_EUR_TO_USD_RATE = 1.10;

    /**
     * Number of decimals used when returning monetary values.
     */
    public const PRECISION = 2;

    /**
     * Currencies supported by the converter.
     *
     * @var array<int, string>
     */
    public const CURRENCIES = ['EUR', 'USD'];

    /**
     * Show the price calculator page.
     */
    public function index(): Response
    {
        return Inertia::render('prices/Index', [
            'defaultRate' => self::DEFAULT_EUR_TO_USD_RATE,
            'currencies' => [
                ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                ['code' => 'USD', 'name' => 'Dólar estadounidense', 'symbol' => '$'],
            ],
            'example' => [
                'converted' => $this->convertCurrency(100, 'EUR', 'USD'),
                'discounted' => $this->calculateDiscount(100, 20),
            ],
        ]);
    }

    /**
     * Convert an amount between euros and US dollars.
     *
     * The rate expresses how many dollars one euro is worth. When no rate is
     * given, the default EUR -> USD rate is used. USD -> EUR conversions use
     * the same rate in reverse.
     *
     * @throws InvalidArgumentException
     */
    public function convertCurrency(
        float $amount,
        string $from,
        string $to,
        ?float $rate = null,
    ): float {
        if ($amount < 0) {
            throw new InvalidArgumentException('The amount cannot be negative.');
        }

        $from = strtoupper($from);
        $to = strtoupper($to);

        if (! in_array($from, self::CURRENCIES, true) || ! in_array($to, self::CURRENCIES, true)) {
            throw new InvalidArgumentException('Only EUR and USD currencies are supported.');
        }

        if ($from === $to) {
            return round($amount, self::PRECISION);
        }

        $rate ??= self::DEFAULT_EUR_TO_USD_RATE;

        if ($rate <= 0) {
            throw new InvalidArgumentException('The exchange rate must be greater than zero.');
        }

        $converted = $from === 'EUR'
            ? $amount * $rate
            : $amount / $rate;

        return round($converted, self::PRECISION);
    }

    /**
     * Calculate the final price after applying a percentage discount.
     *
     * @throws InvalidArgumentException
     */
    public function calculateDiscount(float $price, float $percentage): float
    {
        return round(
            $price - $this->discountAmount($price, $percentage),
            self::PRECISION,
        );
    }

    /**
     * Calculate how much money a percentage discount saves.
     *
     * @throws InvalidArgumentException
     */
    public function discountAmount(float $price, float $percentage): float
    {
        if ($price < 0) {
            throw new InvalidArgumentException('The price cannot be negative.');
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('The discount percentage must be between 0 and 100.');
        }

        return round($price * ($percentage / 100), self::PRECISION);
    }

    /**
     * Convert an amount and return the result as JSON.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'to' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'rate' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $amount = (float) $validated['amount'];
        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);
        $rate = isset($validated['rate'])
            ? (float) $validated['rate']
            : self::DEFAULT_EUR_TO_USD_RATE;

        return response()->json([
            'amount' => round($amount, self::PRECISION),
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'converted' => $this->convertCurrency($amount, $from, $to, $rate),
        ]);
    }

    /**
     * Calculate a discount and return the result as JSON.
     */
    public function discount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'percentage' => ['required', 'numeric', 'between:0,100'],
        ]);

        $price = (float) $validated['price'];
        $percentage = (float) $validated['percentage'];

        return response()->json([
            'price' => round($price, self::PRECISION),
            'percentage' => $percentage,
            'discount' => $this->discountAmount($price, $percentage),
            'final_price' => $this->calculateDiscount($price, $percentage),
        ]);
    }
}
