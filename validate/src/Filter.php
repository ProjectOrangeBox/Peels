<?php

declare(strict_types=1);

namespace peels\validate;

use orange\framework\base\Singleton;
use peels\validate\interfaces\FilterInterface;
use orange\framework\interfaces\InputInterface;
use peels\validate\interfaces\ValidateInterface;

/**
 * Class to pull data from input with validation & filtering rules as well as support for default values
 *
 * additionally provides a method to "remap" input as needed
 */
class Filter extends Singleton implements FilterInterface
{
    protected ValidateInterface $validateService;

    protected array $dataSets = [];

    protected function __construct(array $config, ValidateInterface $validate)
    {
        $this->validateService = $validate;

        foreach ($config as $key => $array) {
            $this->$key = $array;
        }
    }

    /**
     * magic methods to allow setting of arrays of data to be filtered
     * then calling methods to filter specific keys from those arrays
     * $filter->request = $_REQUEST;
     *
     * @param string $setName
     * @param array $value
     * @return void
     */
    public function __set(string $setName, mixed $value): void
    {
        $this->set($setName, $value);
    }

    public function set(string $setName, mixed $value): self
    {
        $this->dataSets[$setName] = $value;

        return $this;
    }

    /**
     * magic method to allow calling of filtering on previously set arrays
     * $name = $filter->request('name','string|length[32]','defaultName');
     *
     * @param string $setName
     * @param array $arguments
     * @return mixed
     */
    public function __call($setName, $arguments): mixed
    {
        $key = $arguments[0] ?? '';
        $rules = $arguments[1] ?? '';
        $default = $arguments[2] ?? null;

        if (isset($this->dataSets[$setName])) {
            if (is_array($this->dataSets[$setName])) {
                $value = $this->dataSets[$setName][$key];
            } else {
                $value = $this->dataSets[$setName];
            }
             
            $return = $this->value($value, $rules);
        } else {
            $return = $default;
        }

        return $return;
    }

    /**
     * Single value filter
     * $value = $filter->value($foobar,'readable');
     * $value = $filter->value($foobar,['string','maxlength[20]']);
     *
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     */
    public function value(mixed $value, string|array $rules): mixed
    {
        return $this->runRule($value, $rules);
    }

    /**
     * filter multiple values at once
     *
     * @param array $values
     * @param array $keysRules
     * @return array
     */
    public function values(array $values, array $keysRules): array
    {
        $return = [];

        foreach ($keysRules as $key => $rules) {
            $return[$key] = isset($values[$key]) ? $this->value($values[$key], $rules) : null;
        }

        return $return;
    }

    /**
     * Run the validation rules against the value
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     *
     * @param mixed $value
     * @param string|array $rules
     * @return mixed
     */
    protected function runRule(mixed $value, string|array $rules): mixed
    {
        if (is_array($rules)) {
            $rules = implode($this->validateService->getDelimiters('rule'), $rules);
        }

        // throws exception on fail
        // returns value on success
        return $this->validateService->throwExceptionOnFailure(true)->value($value, $rules);
    }
}
