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
    protected InputInterface $inputService;

    protected function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validateService = $validate;
        $this->inputService = $input;
    }

    /**
     * Example:
     * this will throw an error if the rules fail
     * so be sure to use rules that do not throw errors
     * for example, use 'castString' instead of 'string' or 'required
     * examples:
     *
     * $value = $filter->query('name', 'castString', 'defaultName'); // with default
     * or
     * $value = $filter->query('name', 'castString'); // no default
     * or
     * $value = $filter->query('name'); // no rules, no default
     * or
     * $value = $filter->query('name', '', 'defaultName'); // no
     * or
     * $value = $filter->request('name', 'email', 'me@my'); // default
     * or
     * $value = $filter->request('name', 'email'); // no default
     * or
     * $value = $filter->request('name', 'castString');
     */
    public function request(array|string $key, string $rules = '', mixed $default = null): mixed
    {
        return is_array($key) ? $this->multiple($key, 'request') : $this->single($key, $rules, $default, 'request');
    }

    public function query(array|string $key, string $rules = '', mixed $default = null): mixed
    {
        return is_array($key) ? $this->multiple($key, 'request') : $this->single($key, $rules, $default, 'query');
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
        return $this->validateService->throwExceptionOnFailure(true)->input($value, $rules)->value();
    }

    protected function single(string $key, string $rules, mixed $default, string $method): mixed
    {
        return $this->value($this->inputService->$method($key, $default), $rules);
    }

    protected function multiple(array $mutiple, string $method): array
    {
        $filtered = [];

        // for each key and rule...
        foreach ($mutiple as $key => $rules) {
            $filtered[$key] = $this->single($key, $rules, '', $method);
        }

        return $filtered;
    }
}
