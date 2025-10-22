<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

/**
 * Contract for validating scalar and structured input using named rule sets.
 *
 * @example
 *   $validData = $validate
 *       ->values(['name' => 'Johnny Appleseed', 'age' => '54'])
 *       ->for('name', 'isString|notEmpty', 'Name')
 *       ->for('age', 'isInt|between[18,110]|notEmpty', 'Age')
 *       ->run();
 * @example
 *   $single = $validate->value('Johnny', 'isString|notEmpty', 'Name');
 *
 * @package peels\validate\interfaces
 */
interface ValidateInterface
{
    /**
     * Reset the validator to an empty state.
     *
     * @return self
     */
    public function reset(): self;

    /**
     * Retrieve the rule delimiters or a specific delimiter if a key is provided.
     *
     * @param string $needle Optional delimiter identifier.
     * @return string|array
     */
    public function getDelimiters(string $needle = ''): string|array;

    /**
     * Register a single validation rule handler.
     *
     * @param string $name Rule identifier.
     * @param string $class Fully qualified class name implementing the rule.
     * @return self
     */
    public function addRule(string $name, string $class): self;

    /**
     * Register multiple validation rule handlers in bulk.
     *
     * @param array $rules Mapping of rule identifiers to handler class names.
     * @return self
     */
    public function addRules(array $rules): self;

    /**
     * Validate a single value using the provided rule expression.
     *
     * @param mixed $input Value to validate.
     * @param string $rules Pipe-delimited or otherwise parsed rule definition.
     * @param string|null $human Optional human-readable label for error messages.
     * @return mixed
     */
    public function value(mixed $input, string $rules, ?string $human = null): mixed;

    /**
     * Supply the data set that subsequent `for()` calls operate on.
     *
     * @param array $input Associative array of input values.
     * @return self
     */
    public function values(array $input): self;

    /**
     * Attach validation rules to a specific key within the current data set.
     *
     * @param string $name Input key to validate.
     * @param array|string $rules Rule definition(s) to apply to the key.
     * @param string|null $human Optional human-readable label for error messages.
     * @return self
     */
    public function for(string $name, array|string $rules, ?string $human = null): self;

    /**
     * Register validation rules for multiple keys in bulk.
     *
     * @param array $each Associative array defining keys, rules, and labels.
     * @return self
     */
    public function forEach(array $each): self;

    /**
     * Execute validation for the configured data set and rules.
     *
     * @return mixed Validated data set or rule-defined return value.
     */
    public function run(): mixed;

    /**
     * Prevent further rule processing after the current state.
     *
     * @return self
     */
    public function stopProcessing(): self;

    /**
     * Toggle throwing exceptions when validation fails.
     *
     * @return self
     */
    public function throwExceptionOnFailure(): self;

    /**
     * Override the delimiter used within nested notation (e.g., dot-notation).
     *
     * @param string $delimiter Delimiter character(s).
     * @return self
     */
    public function changeNotationDelimiter(string $delimiter): self;

    /**
     * Disable nested notation processing for subsequent validations.
     *
     * @return self
     */
    public function disableNotation(): self;

    /**
     * Record an error message for later inspection.
     *
     * @param string $errorMsg Error message text.
     * @param string $human Optional human-friendly label for the input.
     * @param string $options Additional context or options for the error.
     * @param string $rule Rule name that triggered the error.
     * @param string $input Input key or value associated with the error.
     * @return self
     */
    public function addError(string $errorMsg, string $human = '', string $options = '', string $rule = '', string $input = ''): self;

    /**
     * Determine if at least one error has been recorded.
     *
     * @return bool
     */
    public function hasError(): bool;

    /**
     * Determine if multiple errors have been recorded.
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Retrieve the most recent error message.
     *
     * @return string
     */
    public function error(): string;

    /**
     * Retrieve all recorded error messages.
     *
     * @return array
     */
    public function errors(): array;

    /**
     * Determine if validation completed without errors.
     *
     * @return bool
     */
    public function hasNoErrors(): bool;
}
