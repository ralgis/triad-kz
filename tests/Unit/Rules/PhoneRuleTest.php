<?php

declare(strict_types=1);

use App\Rules\PhoneRule;
use Illuminate\Translation\PotentiallyTranslatedString;

function validatePhone(mixed $value): array
{
    $errors = [];
    (new PhoneRule)->validate('phone', $value, function (string $msg, ?string $param = null) use (&$errors) {
        $errors[] = $msg;

        return new PotentiallyTranslatedString($msg, app('translator'));
    });

    return $errors;
}

it('normalizes 8-prefixed numbers to +7', function () {
    expect(PhoneRule::normalize('87001234567'))->toBe('+77001234567');
});

it('normalizes 7-prefixed (no +) numbers', function () {
    expect(PhoneRule::normalize('77001234567'))->toBe('+77001234567');
});

it('normalizes already-canonical numbers', function () {
    expect(PhoneRule::normalize('+77001234567'))->toBe('+77001234567');
});

it('normalizes pretty-printed numbers', function () {
    expect(PhoneRule::normalize('+7 (700) 123-45-67'))->toBe('+77001234567')
        ->and(PhoneRule::normalize('8 700 123 45 67'))->toBe('+77001234567')
        ->and(PhoneRule::normalize('7.700.123.45.67'))->toBe('+77001234567');
});

it('normalizes bare 10-digit numbers (no country code) to +7', function () {
    expect(PhoneRule::normalize('7001234567'))->toBe('+77001234567');
});

it('validate accepts strings that normalize cleanly', function () {
    expect(validatePhone('+77001234567'))->toBe([])
        ->and(validatePhone('8 (700) 123-45-67'))->toBe([])
        ->and(validatePhone('77001234567'))->toBe([]);
});

it('rejects too-short or too-long numbers', function () {
    expect(validatePhone('+700'))->not->toBe([])
        ->and(validatePhone('+770012345670'))->not->toBe([]);
});

it('rejects non-string input', function () {
    expect(validatePhone(null))->not->toBe([])
        ->and(validatePhone(87001234567))->not->toBe([]);
});
