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
     * $filter = Filter::instance();
     * get from any input method (get, post, put, delete, etc)
     * first param is input key
     * second param is rules (string or array)
     * third param is default value if not present
     * if no rules and no default, returns null if not present
     * if rules and no default, returns null if not present or throws error if invalid
     * if no rules and default, returns default if not present
     * if rules and default, returns default if not present or throws error if invalid
     * rules are validation rules, but should be filters (no errors) not validators (errors)
     * this will throw an error if the rules fail
     * so be sure to use rules that do not throw errors
     * for example, use 'castString' instead of 'string' or 'required
     * examples:
     *
     * $value = $filter->get('name', 'castString', 'defaultName'); // with default
     * or
     * $value = $filter->get('name', 'castString'); // no default
     * or
     * $value = $filter->get('name'); // no rules, no default
     * or
     * $value = $filter->get('name', '', 'defaultName'); // no
     * or
     * $value = $filter->post('name', 'email', 'me@my'); // default
     * or
     * $value = $filter->post('name', 'email'); // no default
     * or
     * $value = $filter->post('name', 'castString');
     */
    public function __call(string $method, array $arguments): mixed
    {
        // allow these to pass though to input
        $inputKey = $arguments[0] ?? null;
        $default = $arguments[2] ?? null;
        $rules = $arguments[1] ?? '';

        $method = strtolower($method);

        // copy everything
        // throws error if unavailable so test with inputService->has('post');
        // or something like that before calling __call()
        $inputArray = $this->inputService->$method();

        // if it doesn't exist then use the default
        if (isset($inputArray[$inputKey])) {
            // validate a single value against rules
            $default = $inputArray[$inputKey] = $this->value($inputArray[$inputKey], $rules);

            // put it all back
            $this->inputService->replace([$method => $inputArray]);
        }

        return $default;
    }

    /**
     * Batch request filter
     * $clean = $filter->request([
     *   'name' => 'readable',
     *  'email' => 'email',
     * ]);
     * or
     * $clean = $filter->request([
     *   'name' => ['string','maxlength[20]'],
     *   'email' => ['email','required'],
     * ]);
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     *
     * @param array $inputKeysRules
     * @param string|null $method
     * @return array
     */
    public function request(array $inputKeysRules, ?string $method = null): array
    {
        if (!$method) {
            // guess
            $method = $this->inputService->requestMethod(true);
        } else {
            $method = strtolower($method);
        }

        $clean = [];

        // get all input for this request type
        $inputArray = $this->inputService->$method();

        // for each key and rule...
        foreach ($inputKeysRules as $inputKeys => $rules) {
            // let's make sure we have something to filter
            $value = $inputArray[$inputKeys] ?? '';

            $clean[$inputKeys] = $inputArray[$inputKeys] = $this->value($value, $rules);
        }

        // put it all back
        $this->inputService->replace([$method => $inputArray]);

        return $clean;
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
}
