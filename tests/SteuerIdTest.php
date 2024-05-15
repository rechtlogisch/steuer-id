<?php

use Rechtlogisch\SteuerId\SteuerId;

beforeEach(function () {
    putenv('STEUERID_PRODUCTION=false');
});

it('validates official examples of steuer-id', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
})->with('official-examples');

it('returns true for a valid steuer-id', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
})->with('valid');

it('returns true for a valid steuer-id with spaces', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
})->with('valid-with-spaces');

it('returns false for an invalid steuer-id', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->not->toBeEmpty();
})->with('invalid');

it('validates a test steuer-id', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
})->with('test-ids');

it('validates kontist steuer-id', function (string $steuerId, bool $expectedResult) {
    $result = (new SteuerId($steuerId))->validate()->isValid();
    expect($result)->toBe($expectedResult);
})->with('taxid-kontist');

it('throws a type error when nothing provided as steuer-id', function () {
    /** @noinspection PhpParamsInspection */
    new SteuerId(); /** @phpstan-ignore-line */
})->throws(TypeError::class);

it('throws a type error when null provided as steuer-id', function () {
    new SteuerId(null); /** @phpstan-ignore-line */
})->throws(TypeError::class);

it('does not throw a type error when int provided as steuer-id', function () {
    // PHP casts int to string due to the type hint in class constructor
    // https://www.php.net/manual/en/language.types.string.php#language.types.string.casting
    /** @phpstan-ignore-next-line */
    $result = (new SteuerId(12345678911))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('returns false and specific error message when non-digits provided', function () {
    $result = (new SteuerId('a'))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Only digits allowed.');
});

it('returns false and specific error message when empty input provided', function () {
    $result = (new SteuerId(''))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Please provide a non-empty input as Steuer-ID.');
});

it('returns false and specific error message when input to short', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Steuer-ID must be 11 digits long.');
})->with([
    '1',
    '1234567890',
]);

it('returns false and specific error message when a test steuer-id is provided on production', function () {
    putenv('STEUERID_PRODUCTION=true');
    $result = (new SteuerId('02476291358'))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Test Steuer-IDs (first digit `0`) are not allowed.');
});

it('returns true when a test steuer-id is provided on non-production', function () {
    putenv('STEUERID_PRODUCTION=false');

    $result = (new SteuerId('02476291358'))->validate();
    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('returns false and specific error message when a steuer-id does not contain any repeated digits', function () {
    $result = (new SteuerId('12345678901'))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Inputted Steuer-ID must contain one repeated digit.');
});

it('returns false and specific error message when a steuer-id does contain more than one repeated digits', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('Inputted Steuer-ID must contain one repeated digit.');
})->with([
    '12123456789',
    '11111222223',
]);

it('returns false and specific error message an exception when a steuer-id contains a repeated more than two or three times', function ($steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('One digit must be repeated two or three times as a constraint.');
})->with([
    '12131415678',
    '99999999999',
]);

it('returns false and specific error message when a steuer-id contains an invalid chain of digits', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()[0] ?? null)->toContain('can\'t build a consecutive chain of three repeated digits.');
})->with([
    '12345678111',
    '99912345678',
]);

it('does not throw an exception when a steuer-id contains chain of two digits', function (string $steuerId, ?string $error) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeBool();

    if ($error === null) {
        expect($result->getErrors())->toBeEmpty();
    } else {
        expect($result->getErrors()[0] ?? null)->toBe($error);
    }
})->with([
    ['12345678911', null],
    ['99123456789', 'Check digit in the provided Steuer-ID is invalid.'],
    ['99123456780', 'Check digit in the provided Steuer-ID is invalid.'],
]);

it('returns checkDigit `0` for steuer-id with checksum `10`', function (string $steuerId) {
    $result = (new SteuerId($steuerId))->validate();

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
})->with([
    '12345678920',
]);
