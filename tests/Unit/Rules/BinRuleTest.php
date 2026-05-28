<?php

declare(strict_types=1);

use App\Rules\BinRule;
use Illuminate\Translation\PotentiallyTranslatedString;

function validateBin(mixed $value): array
{
    $errors = [];
    (new BinRule)->validate('bin', $value, function (string $msg, ?string $param = null) use (&$errors) {
        $errors[] = $msg;

        return new PotentiallyTranslatedString($msg, app('translator'));
    });

    return $errors;
}

it('accepts a valid 12-digit БИН', function () {
    expect(validateBin('123456789012'))->toBe([]);
});

it('rejects fewer than 12 digits', function () {
    expect(validateBin('12345678901'))->not->toBe([]);
});

it('rejects more than 12 digits', function () {
    expect(validateBin('1234567890123'))->not->toBe([]);
});

it('rejects non-digit characters', function () {
    expect(validateBin('12345678901a'))->not->toBe([])
        ->and(validateBin('123-456-789-012'))->not->toBe([]);
});

it('rejects empty / non-string', function () {
    expect(validateBin(''))->not->toBe([])
        ->and(validateBin(null))->not->toBe([])
        ->and(validateBin(123456789012))->not->toBe([]);
});
