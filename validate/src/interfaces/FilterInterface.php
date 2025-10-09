<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface FilterInterface
{
    /**
     * filter single input variable
     *
     * @param array $inputKeysRules
     * @param string|null $method
     * @return array
     */
    public function request(array|string $key, string $rules = '', mixed $default = null): mixed;
    public function query(array|string $key, string $rules = '', mixed $default = null): mixed;
    /**
     * $value = $filterService->value('abc123','visible|length[32]');
     * $value = $filterService->value('abc123',['visible','length[32]']);
     *
     * @param mixed $value
     * @param string|array $rules
     * @return mixed
     */
    public function value(mixed $value, string|array $rules): mixed;
}
