<?php

declare(strict_types=1);

namespace peels\bitwise;

use peels\bitwise\exceptions\BitNotFound;

/**
 * Manage named bitwise flags and provide helpers to inspect and mutate them.
 */
class BitWise
{
    protected int $mask = 0;
    protected array $bits = [];
    protected int $nextBit = 1;
    protected int $maximumBits = 32;

    /**
     * Create a new bitwise helper and register the provided bit names.
     *
     * @param array<int, string> $bitValues List of bit identifiers to register.
     */
    public function __construct(array $bitValues = [])
    {
        foreach ($bitValues as $bit) {
            $this->addBit($bit);
        }

        // these are always available
        $this->addBitValue('ALWAYS', 0, false);
        $this->addBitValue('NONE', 0, false);
        $this->addBitValue('EVERYTHING', pow(2, $this->maximumBits) - 1, false);
    }

    /**
     * Magic getter to treat bits as properties.
     *
     *  `$bitwise->warning` is equivalent to `$bitwise->isSet('warning')`.
     *
     * @param string $bit Bit identifier to check.
     *
     * @return bool True when the bit is set.
     */
    public function __get(string $bit): bool
    {
        return $this->isSet($bit);
    }

    /**
     * Turn on one or more bits.
     *
     * Examples:
     * `$bitwise->turnOn('error', 'warning')` will enable both the error and warning bits.
     * `$bitwise->turnOn(['error', 'warning'])` will do the same.
     * `$bitwise->turnOn('error')->turnOn('warning')` will also do the same.
     * `$bitwise->turnOn('error', 'warning')->turnOff('error')` will enable warning only.
     *
     * @param string|string[] ...$bits Bit identifiers or arrays of bit identifiers to enable.
     *
     * @return $this
     */
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

    /**
     * Turn off one or more bits.
     *
     * @param string|string[] ...$bits Bit identifiers or arrays of bit identifiers to disable.
     *
     * @return $this
     */
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

    /**
     * Reset all bits to the neutral state.
     * Example:
     * `$bitwise->reset()` will turn off all bits.
     * `$bitwise->turnOn('error')->reset()` will turn off the error bit.
     * `$bitwise->turnOn('error', 'warning')->reset()` will turn off both bits.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->mask = 0;

        return $this;
    }

    /**
     * Determine whether a bit exists and optionally fetch its integer value.
     *
     * @param string $bit Bit identifier to inspect.
     * @param bool $throwException When true, throw if the bit does not exist.
     * @param bool $returnInteger When true, return the bit's integer value instead of a boolean.
     *
     * @return bool|int True when the bit exists, or the integer value when requested.
     *
     * @throws BitNotFound When the bit is missing and exception throwing is enabled.
     */
    public function hasBit(string $bit, bool $throwException = false, bool $returnInteger = false): bool|int
    {
        $normalizedBit = strtoupper($bit);

        $has = isset($this->bits[$normalizedBit]);

        if (!$has && $throwException) {
            throw new BitNotFound($normalizedBit);
        }

        return ($returnInteger) ? $this->bits[$normalizedBit] ?? 0 : $has;
    }

    /**
     * Check whether the given bit is currently set on the mask.
     *
     * @param string $bit Bit identifier to check.
     *
     * @return bool True when the bit is set.
     */
    public function isSet(string $bit): bool
    {
        $bitInteger = $this->hasBit($bit, true, true);

        return ($this->mask & $bitInteger) == $bitInteger;
    }

    /**
     * Add one or more bits while preserving existing configuration.
     *
     * Examples:
     * `$bitwise->addBit('error')` will register the error bit.
     * `$bitwise->addBit(['error', 'warning'])` will register both error and warning bits.
     * `$bitwise->addBit('error')->addBit('warning')` will also register both bits.
     * `$bitwise->addBit('error', 'warning')` will register both bits as well.
     *
     * @param string|string[] ...$bits Bit identifiers or arrays of bit identifiers to register.
     *
     * @return $this
     */
    public function addBit(): self
    {
        $args = func_get_args();

        return $this->addBits(...$args);
    }

    /**
     * Add multiple bits to the known set.
     *
     * Examples:
     * `$bitwise->addBits('error')` will register the error bit.
     * `$bitwise->addBits(['error', 'warning'])` will register both error and warning bits.
     * `$bitwise->addBits('error')->addBits('warning')` will also register both bits.
     * `$bitwise->addBits('error', 'warning')` will register both bits as well.
     *
     * @param string|string[] ...$bits Bit identifiers or arrays of bit identifiers to register.
     *
     * @return $this
     */
    public function addBits(): self
    {
        $args = func_get_args();

        if (is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $bit) {
            // register the bit with the next available integer value
            $this->addBitValue($bit, $this->nextBit, true);
        }

        return $this;
    }

    /**
     * Provide helpful data when the instance is dumped.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo()
    {
        return [
            'mask' => $this->mask,
            'bits' => $this->bits,
        ];
    }

    /**
     * Resolve the integer value for the named bit.
     *
     * if the bit does not exist, an exception will be thrown.
     *
     * @param string $bit Bit identifier to resolve.
     *
     * @return int Integer representation of the bit.
     */
    protected function getInt(string $bit): int
    {
        return $this->hasBit($bit, true, true);
    }

    /**
     * Store an integer value for the given bit name.
     *
     * Throws exceptions when the value would exceed the maximum bits
     * or when the value is already assigned to another bit.
     *
     * Examples:
     * `$this->addBitValue('error', 1, true)` will assign the error bit the value 1 and prepare 2 for the next bit.
     * `$this->addBitValue('warning', 2, true)` will assign the warning bit the value 2 and prepare 4 for the next bit.
     * `$this->addBitValue('info', 4, false)` will assign the info bit the value 4 and keep 4 for the next bit.
     * `$this->addBitValue('debug', 4, true)` will throw an exception because 4 is already assigned.
     * `$this->addBitValue('trace', 4294967296, true)` will throw an exception because it exceeds 32 bits.
     *
     * @param string $bit Bit identifier.
     * @param int $value Integer value associated with the bit.
     * @param bool $incrementNextBit When true, prepare the next available bit value.
     *
     * @return void
     */
    protected function addBitValue(string $bit, int $value, bool $incrementNextBit): void
    {
        // if the bit is new, validate the value
        if (!isset($this->bits[$bit])) {
            // ensure we don't exceed the maximum number of bits
            if ($value >= pow(2, $this->maximumBits)) {
                throw new \OverflowException("Cannot add bit '{$bit}': maximum number of bits ({$this->maximumBits}) exceeded.");
            }
            // ensure the value is not already assigned to another bit
            if (in_array($value, $this->bits, true)) {
                throw new \InvalidArgumentException("Cannot add bit '{$bit}': value '{$value}' is already assigned to another bit.");
            }
        }

        // store the bit value
        $this->bits[strtoupper($bit)] = $value;

        // prepare the next available bit value
        if ($incrementNextBit) {
            // double the next bit value
            $this->nextBit *= 2;
        }
    }
}
