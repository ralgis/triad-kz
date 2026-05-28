<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Kazakhstan / Russia mobile phone, E.164-style: `+7XXXXXXXXXX`.
 *
 * Accepts the conventional input variants — `8XXXXXXXXXX`, `7XXXXXXXXXX`,
 * `+7 (XXX) XXX-XX-XX`, dots, dashes, spaces — and validates after
 * stripping. Use `PhoneRule::normalize($raw)` to get the canonical form
 * for storage.
 *
 * Pure-MSISDN format, no extensions (no `,123` after the number). That's
 * intentional: our admins call back over normal cellular, no PBX.
 */
final class PhoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Поле :attribute должно быть строкой.');

            return;
        }

        $normalized = self::normalize($value);
        if (preg_match('/^\+7\d{10}$/', $normalized) !== 1) {
            $fail('Поле :attribute должно содержать номер в формате +7XXXXXXXXXX.');
        }
    }

    /**
     * Strip everything except digits, then prepend +7 if the result starts
     * with 8 or a bare 11-digit number starting with 7.
     */
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
            return '+7'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '+7'.$digits;
        }

        return '+'.$digits;
    }
}
