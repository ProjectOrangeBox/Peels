<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

/**
 * Defines the contract for mapping rule sets to values and executing validation filters.
 *
 * @package peels\validate\interfaces
 */
interface FilterInterface
{
    public function __set(string $setName, mixed $value): void;
    public function set(string $setName, mixed $value): self;

    public function __call($setName, $arguments): mixed;

    public function value(mixed $value, string|array $rules): mixed;
    public function values(array $values, array $keysRules): array;
}
