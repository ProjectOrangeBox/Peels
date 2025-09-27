<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\Filter;
use peels\validate\WildNotation;
use peels\validate\exceptions\RuleFailed;
use orange\framework\interfaces\InputInterface;
use peels\validate\interfaces\ValidateInterface;

/**
 * Class ValidJson
 * Validates JSON data against a set of rules.
 * Uses WildNotation to access nested values within the JSON structure.
 *
 * @package peels\validate
 */
class ValidJson extends Filter
{
    // uses the Validate service to perform validation
    protected ValidateInterface $validateService;
    // uses the Input service to fetch input data if needed
    protected InputInterface $inputService;

    /**
     * Constructor
     *
     * @param ValidateInterface $validate
     * @param InputInterface $input
     * @return void
     */
    protected function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validateService = $validate;
        $this->inputService = $input;
    }

    /**
     * Validate a value against a set of rules.
     *
     * @param mixed $value
     * @param string|array $rules
     * @return mixed
     * @throws RuleFailed
     */
    public function value(mixed $value, string|array $rules): mixed
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $this->runRule($value, $rule);
            }
        } else {
            $this->runRule($value, $rules);
        }

        return true;
    }

    /**
     * validateJson[isString(person.name.first)]
     * validateJson[isArray(person.children)]
     * validateJson[isString(person.children.*.name.first)]
     * validateJson[isCountLessThan(person.children),20]
     * validateJson[isOneOf(person.color),red,green,blue]
     *
     * @param mixed $json
     * @param string|array $rule
     * @return mixed
     * @throws RuleFailed
     */
    protected function runRule(mixed $json, string|array $rule): mixed
    {
        // if a string, decode it
        if (is_string($json)) {
            $json = json_decode($json, true);
        }

        // if not an object or array, fail
        if (!is_object($json) && !is_array($json)) {
            throw new RuleFailed('%s is not a valid JSON');
        }

        // parse out function name and dot notation
        preg_match('/(?<rule>[^\(]+)\((?<dot>[^\)]+)\),*(?<options>.*)/i', $rule, $matches, PREG_OFFSET_CAPTURE, 0);

        $dotNotation = $matches['dot'][0];

        $rule = $matches['rule'][0] . '[' . $matches['options'][0] . ']';

        $value = (new WildNotation($json))->get($dotNotation);

        /**
         * returns an array
         * isArray(people.*)
         *
         * foreach
         * isBool(people.*.male)
         */
        if (is_array($value) && substr($dotNotation, -1) != '*') {
            foreach ($value as $v) {
                // throws exception on fail
                // returns value on success
                $this->validateService->throwExceptionOnFailure(true)->input($v, $rule);
            }
        } else {
            // throws exception on fail
            // returns value on success
            $this->validateService->throwExceptionOnFailure(true)->input($value, $rule);
        }

        return $json;
    }
}
