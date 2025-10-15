<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

/**
 * $data = [
 *  'name'=>'Johnny Appleseed',
 *  'age'=>'54',
 * ];
 * $validData = $validate
 *  ->values($data)
 *  ->for('name','isString|notEmpty','Name')
 *  ->for('age','isInt|between[18,110]|notEmpty','Age')
 *  ->run();
 * 
 * $validData = $validate
 *  ->values($data)
 *  ->for(['name'=>['isString|notEmpty','Name'],'age'=>['isInt|between[18,110]|notEmpty','Age'])
 *  ->run();
 *
 * $single = 'Johnny';
 * $validData = $validate->value($single,'isString|notEmpty','Name');
 *
 * @package peels\validate\interfaces
 */
interface ValidateInterface
{
    public function reset(): self;
    public function getDelimiters(string $needle = ''): string|array;

    public function addRule(string $name, string $class): self;
    public function addRules(array $rules): self;

    public function value(mixed $input, string $rules, ?string $human = null): mixed;

    public function values(array $input): self;
    public function for(string $name, array|string $rules, ?string $human = null): self;
    public function forEach(array $each): self;
    public function run(): mixed;

    public function stopProcessing(): self;
    public function throwExceptionOnFailure(): self;

    public function changeNotationDelimiter(string $delimiter): self;
    public function disableNotation(): self;

    // internal error handling - this way we can capture more that 1
    public function addError(string $errorMsg, string $human = '', string $options = '', string $rule = '', string $input = ''): self;
    public function hasError(): bool;
    public function hasErrors(): bool;
    public function error(): string;
    public function errors(): array;
    public function hasNoErrors(): bool;
}
