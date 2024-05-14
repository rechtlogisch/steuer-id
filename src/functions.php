<?php

declare(strict_types=1);

use Rechtlogisch\SteuerId\Dto\ValidationResult;
use Rechtlogisch\SteuerId\SteuerId;

function validateSteuerId(string $input): ValidationResult
{
    return (new SteuerId($input))->validate();
}

function isSteuerIdValid(string $input): ?bool
{
    return (new SteuerId($input))->validate()->isValid();
}
