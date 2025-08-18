<?php

use Rechtlogisch\SteuerId\Dto\ValidationResult;

beforeEach(function () {
    putenv('STEUERID_PRODUCTION=false');
});

it('validates a steuer-id with the global validateSteuerId() function', function () {
    $result = validateSteuerId('02476291358');

    expect($result)->toBeInstanceOf(ValidationResult::class)
        ->and($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('validates a steuer-id with the global isValidSteuerId() function', function () {
    $result = isSteuerIdValid('02476291358');

    expect($result)->toBeTrue();
});
