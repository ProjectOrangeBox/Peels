<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface RemapInterface
{
    public function __set(string $setName, array $value): void;
    public function set(string $setName, array $value): self;
    public function __call($setName, $arguments): mixed;
}
