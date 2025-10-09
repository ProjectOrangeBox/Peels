<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface RemapInterface
{
    public function request(array|string $mapping): array;
    public function query(array|string $mapping): array;

    public function array(array $array, array|string $mapping): array;
}
