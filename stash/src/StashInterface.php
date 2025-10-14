<?php

declare(strict_types=1);

namespace peels\stash;

interface StashInterface
{
    public function push(?string $name = null): self;
    public function apply(?string $name = null): false|array;
}
