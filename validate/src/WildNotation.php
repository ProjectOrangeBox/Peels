<?php

declare(strict_types=1);

namespace peels\validate;

use InvalidArgumentException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Class WildNotation
 *
 * Provides functionality to access and manipulate multi-dimensional arrays using a "wildcard" notation.
 *
 * Example usage:
 * $data = [
 *     'users' => [
 *         ['name' => 'Alice', 'age' => 30],
 *         ['name' => 'Bob', 'age' => 25],
 *     ],
 *     'settings' => [
 *         'theme' => 'dark',
 *         'notifications' => true,
 *     ],
 * ];
 *
 * $wild = new WildNotation($data);
 *
 * // Get all user names
 * $names = $wild->get('users.*.name'); // Returns ['Alice', 'Bob']
 *
 * // Get the theme setting
 * $theme = $wild->get('settings.theme'); // Returns 'dark'
 *
 * // Get all ages
 * $ages = $wild->get('users.*.age'); // Returns [30, 25]
 *
 * // Get a non-existing path with a default value
 * $nonExisting = $wild->get('users.*.email', 'not found'); // Returns 'not found'
 *
 * @package peels\validate
 */
class WildNotation
{
    // The array to be manipulated
    protected array $array;
    // Delimiter for nested keys
    protected string $delimiter = '.';
    // Wildcard character for matching
    protected string $wildcard  = '*';

    /**
     * Constructor
     *
     * @param array $array
     * @return void
     */
    public function __construct(array $array = [])
    {
        $this->setArray($array);
    }

    /**
     * Set the array to be manipulated
     *
     * @param array $array
     * @return WildNotation
     */
    public function setArray(array $array = []): self
    {
        $this->array = $array;

        return $this;
    }

    /**
     * Set the delimiter used for nested keys
     *
     * @param string $delimiter
     * @return WildNotation
     * @throws InvalidArgumentException
     */
    public function setDelimiter(string $delimiter): self
    {
        if ($delimiter === '') {
            throw new InvalidArgumentException('The delimiter must not be an empty string.');
        }

        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Set the wildcard character used for matching
     *
     * @param string $wildcard
     * @return WildNotation
     * @throws InvalidArgumentException
     */
    public function setWildcard(string $wildcard): self
    {
        if ($wildcard === '') {
            throw new InvalidArgumentException('The wildcard must not be an empty string.');
        }

        $this->wildcard = $wildcard;

        return $this;
    }

    /**
     * Get a value from the array using wildcard notation
     *
     * @param string $path
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $path, mixed $default = null): mixed
    {
        if (isset($this->array[$path])) {
            $get =  $this->array[$path];
        } elseif ($path === $this->wildcard) {
            $get = $this->array;
        } else {
            $get = $this->search($path, $default);
        }

        return $get;
    }

    /**
     * Search the array for matching paths using wildcard notation
     *
     * @param string $path
     * @param mixed|null $default
     * @return mixed
     */
    protected function search(string $path, mixed $default = null): mixed
    {
        $pathway = [];
        $flatArray = null;

        $segments = explode($this->delimiter, $path);
        $countSegments = count($segments);

        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->array), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($it as $key => $value) {
            $pathway[$it->getDepth()] = $key;

            if ($it->getDepth() + 1 !== $countSegments) {
                continue;
            }

            if ($this->isRealPath($segments, $pathway)) {
                $flatArray[implode($this->delimiter, array_slice($pathway, 0, $it->getDepth() + 1))] = $value;
            }
        }

        if ($flatArray === null) {
            $val = $default;
        } else {
            $val = array_values($flatArray);

            if (is_countable($val) && count($val) === 1) {
                $val = $val[0];
            }
        }

        return $val;
    }

    /**
     * Check if the provided path matches the real path in the array
     *
     * @param array $path
     * @param array $real
     * @return bool
     */
    protected function isRealPath(array $path, array $real): bool
    {
        $success = true;

        if ($path !== $real) {
            $index = 0;
            $success = false;

            foreach ($path as $item) {
                $val = $real[$index] ?? false;

                if (ctype_digit($item)) {
                    $item = (int) $item;
                }

                if ($val === $item) {
                    $success = true;
                } elseif ($item === $this->wildcard) {
                    $success = true;
                } else {
                    // bail on first non match
                    $success = false;
                    break;
                }

                $index++;
            }
        }

        return $success;
    }
}
