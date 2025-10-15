<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    public function __set(string $setName, array $value): void;
    public function set(string $setName, array $value): self;
    public function __call($setName, $arguments): mixed;
    public function value(mixed $value, string|array $rules): mixed;
    public function values(array $values, array $keysRules): array;
}
