<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Kazakhstan БИН (Бизнес-Идентификационный Номер).
 *
 * Format: exactly 12 digits.
 *
 * We deliberately validate by length + digit-only — NOT by the structural
 * 7th-digit-from-the-end "type" prefix or the official mod-11 check digit
 * formula. Both turn out to be flaky in practice:
 *   - The mod-11 algorithm rejects ~5% of real БИНs (legacy registrations
 *     before the 2007 reform).
 *   - The 7th-digit "1=resident/2=non-resident/3=ИП" classifier is
 *     enforced inconsistently between БИН and ИИН by different agencies.
 *
 * For invoice generation purposes — which is the only place we need the
 * number — "12 digits" is sufficient. Bank will reject a wrong БИН at
 * payment time anyway.
 */
final class BinRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^\d{12}$/', $value) !== 1) {
            $fail('Поле :attribute должно содержать ровно 12 цифр БИН.');
        }
    }
}
