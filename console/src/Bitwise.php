<?php

declare(strict_types=1);

namespace peels\console;

use peels\console\exceptions\BitNotFound;

class BitWise
{
    protected int $mask = 0;
    protected array $bits = [];
    protected int $nextBit = 1;
    protected int $maximumBits = 32;

    public function __construct(array $bitValues = [])
    {
        $this->addBitValue('ALWAYS', 0);
        $this->addBitValue('NONE', 0);

        foreach ($bitValues as $bit) {
            $this->addBit($bit);
        }

        $this->addBitValue('EVERYTHING', pow(2, $this->maximumBits) - 1);
    }

    public function __get(string $bit): bool
    {
        return $this->isSet($bit);
    }

    public function turnOn(): self
    {
        $args = func_get_args();

        if (is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $bit) {
            $this->mask |= $this->getInt($bit);
        }

        return $this;
    }

    public function turnOff(): self
    {
        $args = func_get_args();

        if (is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $bit) {
            $this->mask &= $this->getInt($bit);
        }

        return $this;
    }

    public function reset(): self
    {
        $this->mask = 0;

        return $this;
    }

    public function hasBit(string $bit, bool $throwException = false, bool $returnInteger = false): bool|int
    {
        $normalizedBit = strtoupper($bit);

        $has = isset($this->bits[$normalizedBit]);

        if (!$has && $throwException) {
            throw new BitNotFound($normalizedBit);
        }

        return ($returnInteger) ? $this->bits[$normalizedBit] ?? 0 : $has;
    }

    public function isSet(string $bit): bool
    {
        $bitInteger = $this->hasBit($bit, true, true);

        return ($this->mask & $bitInteger) == $bitInteger;
    }

    public function addBit(): self
    {
        $args = func_get_args();

        return $this->addBits(...$args);
    }

    public function addBits(): self
    {
        $args = func_get_args();

        if (is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $bit) {
            if (!$this->hasBit($bit)) {
                $this->addBitValue($bit, $this->nextBit);

                $this->nextBit = $this->nextBit + $this->nextBit;
            }
        }

        return $this;
    }

    public function __debugInfo()
    {
        return ['level' => $this->mask, 'bits' => $this->bits];
    }

    protected function getInt(string $bit): int
    {
        return $this->hasBit($bit, true, true);
    }

    protected function addBitValue(string $bit, int $value): void
    {
        $this->bits[strtoupper($bit)] = $value;
    }
}
