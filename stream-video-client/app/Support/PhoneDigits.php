<?php

namespace App\Support;

final class PhoneDigits
{
    /**
     * Keep digits only for storage and lookup (E.164-style body, no +).
     */
    public static function normalize(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
