<?php

declare(strict_types=1);

namespace Rechtlogisch\SteuerId;

use Rechtlogisch\SteuerId\Dto\ValidationResult;
use Throwable;

class SteuerId
{
    private const LENGTH_STEUER_ID = 11;

    private ValidationResult $result;

    public function __construct(
        public string $input
    ) {
        $this->result = new ValidationResult();

        try {
            $this->guard();
        } catch (Throwable $exception) {
            $exceptionType = get_class($exception);
            $this->result->setValid(false);
            $this->result->addError($exceptionType, $exception->getMessage());
        }
    }

    public function validate(): ValidationResult
    {
        if ($this->result->isValid() === false) {
            return $this->result;
        }

        $hasValidChecksum = mb_substr($this->input, -1) === (string) $this->checkDigit();
        $this->result->setValid($hasValidChecksum);

        if ($hasValidChecksum === false) {
            $this->result->addError(Exceptions\InvalidCheckDigit::class, 'Check digit in the provided Steuer-ID is invalid.');
        }

        return $this->result;
    }

    private function guard(): void
    {
        if (empty($this->input)) {
            throw new Exceptions\InputEmpty('Please provide a non-empty input as Steuer-ID.');
        }

        if (! ctype_digit($this->input)) {
            throw new Exceptions\SteuerIdCanContainOnlyDigits('Only digits are allowed.');
        }

        $this->filter();

        if (($lengthInput = mb_strlen($this->input)) !== self::LENGTH_STEUER_ID) {
            throw new Exceptions\InvalidSteuerIdLength('Steuer-ID must be '.self::LENGTH_STEUER_ID.' digits long. Inputted Steuer-ID has: '.$lengthInput.' digits');
        }

        if ($this->input[0] === '0' && ! $this->allowTestIds()) {
            throw new Exceptions\TestSteuerIdNotSupported('Test Steuer-IDs (first digit `0`) are not allowed.');
        }

        $this->isRepeatedDigitsConstraintFulfilled();
    }

    private function filter(): void
    {
        $this->input = preg_replace('/\D/', '', $this->input);
    }

    private function allowTestIds(): bool
    {
        return getenv('STEUERID_PRODUCTION') !== 'true';
    }

    private function isRepeatedDigitsConstraintFulfilled(): void
    {
        $firstTenDigits = mb_substr($this->input, 0, self::LENGTH_STEUER_ID - 1);
        $countValues = array_count_values(mb_str_split($firstTenDigits));

        $repeatedDigits = array_filter($countValues, static fn (int $item) => $item > 1);
        if (count($repeatedDigits) !== 1) {
            throw new Exceptions\InvalidRepeatedDigit('Inputted Steuer-ID must contain one repeated digit.');
        }

        $repeatedDigit = array_key_first($repeatedDigits);
        $repeats = (int) $repeatedDigits[$repeatedDigit];
        if (
            $repeats !== 2
            && $repeats !== 3
        ) {
            throw new Exceptions\InvalidRepeatsCount("One digit must be repeated two or three times as a constraint. It is repeated: {$repeats} times.");
        }

        $repeatedChainOfDigits = str_repeat((string) $repeatedDigit, $repeats);
        if (
            $repeats === 3
            && str_contains($this->input, $repeatedChainOfDigits)
        ) {
            throw new Exceptions\InvalidChainOfDigits("The repeated digit {$repeatedDigit} can't build a consecutive chain of three repeated digits. Inputted Steuer-ID contains: {$repeatedChainOfDigits}.");
        }
    }

    private function checkDigit(): int
    {
        $modulo = $product = 10;
        $lengthRelevantForCheck = self::LENGTH_STEUER_ID - 1;

        for ($i = 0; $i < $lengthRelevantForCheck; $i++) {
            $digit = (int) $this->input[$i];
            $sum = ($digit + $product) % $modulo;
            if ($sum === 0) {
                $sum = $modulo;
            }
            $product = (2 * $sum) % self::LENGTH_STEUER_ID;
        }
        $checkDigit = self::LENGTH_STEUER_ID - $product;

        return ($checkDigit === $modulo)
            ? 0
            : $checkDigit;
    }
}
