<?php

namespace App\Support\Billing;

class Currency
{
    public static function isValidIsoCode(?string $code): bool
    {
        if (! $code) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z]{3}$/', $code);
    }

    public static function normalize(?string $code, string $fallback = 'USD'): string
    {
        $fallback = strtoupper($fallback ?: 'USD');

        if (! self::isValidIsoCode($code)) {
            return self::isValidIsoCode($fallback) ? $fallback : 'USD';
        }

        return strtoupper($code);
    }

    public static function normalizeForStripe(?string $code, string $fallback = 'usd'): string
    {
        $fallback = strtolower($fallback ?: 'usd');

        $normalized = self::normalize($code, strtoupper($fallback));

        return strtolower($normalized);
    }
}

