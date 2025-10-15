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

        $rulesAsArray = is_array($rule) ? $rule : explode('|', $rule);

        foreach ($rulesAsArray as $rule) {
            // parse out function name and dot notation
            $results = preg_match('/(?<rule>[^\(]+)\((?<dot>[^\)]+)\)(?<options>.*)/i', $rule, $matches, PREG_OFFSET_CAPTURE, 0);

            if ($results === 0 || $results === false) {
                throw new RuleFailed($rule);
            }

            $rule = $matches['rule'][0] . $matches['options'][0];
            $dotNotation = $matches['dot'][0];

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
                    $this->validateService->throwExceptionOnFailure(true)->value($v, $rule);
                }
            } else {
                // throws exception on fail
                // returns value on success
                $this->validateService->throwExceptionOnFailure(true)->value($value, $rule);
            }
        }

        return true;
    }
}
