<?php

declare(strict_types=1);

namespace peels\priority;

/**
 * Priority list helper that collects values and returns them ordered by configured weights.
 */
class Priority
{
    /**
     * Default priority labels used when none are supplied via configuration.
     *
     * @var array<int, string>
     */
    protected array $default = ['veryfirst', 'first', 'low', 'earliest', 'early', 'medium', 'normal', 'late', 'latest', 'last', 'high', 'verylast'];
    /**
     * Map of priority label => numeric weight (higher value means later order).
     *
     * @var array<string, int>
     */
    protected array $priorities = [];
    /**
     * Collected values keyed by a float composite of priority weight and monotonic timestamp.
     *
     * @var array<float, mixed>
     */
    protected array $data = [];
    /**
     * Tracks whether the internal data array has already been sorted.
     */
    protected bool $sorted = false;

    /**
     * When true, values are deduplicated using a sha1 hash.
     */
    protected bool $noDuplicates = false;
    /**
     * Registry of hashes for values already added (used only when deduping).
     *
     * @var array<string, bool>
     */
    protected array $duplicates = [];

    /**
     * @param array<string, mixed> $config Configuration options (no duplicates, priorities list, etc.).
     *
     * @throws \Exception When priorities are not provided in a supported format.
     */
    public function __construct(array $config)
    {
        $this->noDuplicates = $config['no duplicates'] ?? $this->noDuplicates;

        $priorities = $config['priorities'] ?? $this->default;

        if (is_string($priorities)) {
            $priorities = explode(',', $priorities);
        }

        if (!is_array($priorities)) {
            throw new \Exception('Priorities must be provided as an array or , seperated string.');
        }

        $add = floor(100 / count($priorities));
        $value = 1;

        foreach ($priorities as $priority) {
            $this->priorities[$priority] = (int)$value;
            $value = $value + $add;
        }
    }

    /**
     * Allow dynamic calls such as `addNormal()` or `addHigh()` to enqueue values.
     *
     * @param string $name      Method name that encodes the priority label.
     * @param array  $arguments Values to queue.
     *
     * @throws \Exception When the requested priority label is unknown.
     *
     * @return void
     */
    public function __call($name, $arguments)
    {
        if ($name == 'add') {
            // default to "middle"
            $priorityIntValue = 50;
        } else {
            $priority = strtolower(substr($name, 3));

            if (!isset($this->priorities[$priority])) {
                throw new \Exception('Unknown Priority "' . $priority . '".');
            }
            $priorityIntValue = $this->priorities[$priority];
        }

        $this->addDetect($priorityIntValue, $arguments);
    }

    /**
     * Render the prioritised values as a concatenated string.
     */
    public function text(): string
    {
        $outputText = '';

        $this->sort();

        /* now build our output */
        foreach ($this->data as $value) {
            $outputText .= (string)$value;
        }

        return $outputText;
    }

    /**
     * Return the prioritised values as a numerically indexed array.
     *
     * Method name kept short for BC; behaves like `toArray()`.
     *
     * @return array<int, mixed>
     */
    public function array(): array
    {
        $this->sort();

        return array_values($this->data);
    }

    /**
     * Convenience wrapper that returns the prioritised values as JSON.
     */
    public function json(): string
    {
        return json_encode($this->array());
    }

    /**
     * Sort the internal collection once per mutation cycle.
     */
    protected function sort(): void
    {
        if (!$this->sorted) {
            ksort($this->data);

            $this->sorted = true;
        }
    }

    /**
     * Normalise variadic/array arguments and queue each value at the given priority.
     *
     * @param int          $position Priority weight.
     * @param array<mixed> $args     Values to queue (direct or nested under index 0).
     *
     * @return self
     */
    protected function addDetect(int $position, array $args): self
    {
        if (is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $value) {
            $this->add($position, $value);
        }

        return $this;
    }

    /**
     * Add a single value at the given priority weight.
     *
     * @param int   $position Numeric weight representing the priority bucket.
     * @param mixed $value    Value to queue.
     *
     * @return void
     */
    protected function add(int $position, mixed $value): void
    {
        if ($this->noDuplicates) {
            // only calculate the hash once
            // and only if we are testing for no duplicates
            $hash = sha1($value);

            if (!isset($this->duplicates[$hash])) {
                $this->duplicates[$hash] = true;
                // use a composite float key so values with identical priority preserve insertion order
                $this->data[floatval((string)$position . (string)\hrtime(true))] = $value;
                $this->sorted = false;
            }
        } else {
            // append without deduplication, still preserving insertion order within the priority slot
            $this->data[floatval((string)$position . (string)\hrtime(true))] = $value;
            $this->sorted = false;
        }
    }
}
