<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    public function __set(string $setName, array $value): void;
    public function __call($setName, $arguments): mixed;

    /**
     * $value = $filterService->value('abc123','visible|length[32]');
     * $value = $filterService->value('abc123',['visible','length[32]']);
     *
     * @param mixed $value
     * @param string|array $rules
     * @return mixed
     */
    public function value(mixed $value, string|array $rules): mixed;

    public function values(array $values, array $keysRules): array;
}
