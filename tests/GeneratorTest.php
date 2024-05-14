<?php

use Rechtlogisch\SteuerId\SteuerId;

it('generates a steuer-id with checksum `0`', function () {
    $class = new ReflectionClass(SteuerId::class);
    try {
        $method = $class->getMethod('checkDigit');
    } catch (ReflectionException $e) {
        exit($e->getMessage());
    }
    /** @noinspection PhpExpressionResultUnusedInspection */
    $method->setAccessible(true);

    $steuerId = '12345678910';

    while (true) {
        $object = new SteuerId($steuerId);

        try {
            $checkDigit = $method->invokeArgs($object, []);
        } catch (ReflectionException $e) {
            exit($e->getMessage());
        }

        if ($checkDigit !== 0) {
            $nextSteuerId = (int) $steuerId + 10;
            $steuerId = (string) $nextSteuerId;

            continue;
        }

        if ((new SteuerId($steuerId))->validate()->isValid() !== true) {
            $nextSteuerId = (int) $steuerId + 10;
            $steuerId = (string) $nextSteuerId;

            continue;
        }

        break;
    }

    //    echo $steuerId;

    $lastDigit = (int) $steuerId % 10;
    expect($lastDigit)->toBe(0);
});
