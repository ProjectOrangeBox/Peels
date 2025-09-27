<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\Notation;
use orange\framework\base\Factory;
use peels\validate\rules\RuleAbstract;
use peels\validate\exceptions\RuleFailed;
use peels\validate\exceptions\InvalidValue;
use peels\validate\exceptions\RuleNotFound;
use orange\framework\traits\ConfigurationTrait;
use peels\validate\exceptions\ValidationFailed;
use peels\validate\interfaces\ValidateInterface;

class Validate extends Factory implements ValidateInterface
{
    use ConfigurationTrait;

    // array of errors
    protected array $errors = [];

    protected string $currentKey = '';
    protected string $currentRule = '';
    protected string $currentOptions = '';
    protected string $currentErrorMsg = '';

    protected mixed $currentInput = null;

    // flag for a rule to stop processing of further rules for a given field
    protected bool $stopProcessing = false;

    // both off by default
    protected string $notationDelimiter = '';
    protected bool $throwExceptionOnFailure = false;
    protected int $exceptionCode = 406;

    protected string $defaultErrorMsg = '%s is not valid.';

    protected string $ruleDelimiter = '|';
    protected string $optionLeftDelimiter = '[';
    protected string $optionRightDelimiter = ']';

    // public so rules can grab it if they need to
    protected string $defaultOptionDelimiter = ',';

    // array of rule "names" to the classes and methods they call
    protected array $rules = [];

    // local instance of notation
    protected Notation $notation;

    /**
     * Constructor
     *
     * @param array $config Configuration options that override the defaults
     */
    protected function __construct(array $config)
    {
        $this->config = $this->mergeConfigWith($config);

        // default error message if one isn't given
        $this->defaultErrorMsg = $this->config['defaultErrorMsg'] ?? $this->defaultErrorMsg;

        // if using notation to indicate drilling down into arrays or classes what is the indicator?
        $this->notationDelimiter = $this->config['notationDelimiter'] ?? $this->notationDelimiter;

        // should we throw an exception when we have validation errors?
        $this->throwExceptionOnFailure = $this->config['throwExceptionOnFailure'] ?? $this->throwExceptionOnFailure;

        // what error code should we use for exceptions
        $this->exceptionCode = $this->config['exceptionCode'] ?? $this->exceptionCode;

        // a string of rules is separated by
        $this->ruleDelimiter = $this->config['ruleDelimiter'] ?? $this->ruleDelimiter;

        // the left and right option Delimiters
        $this->optionLeftDelimiter = $this->config['optionLeftDelimiter'] ?? $this->optionLeftDelimiter;
        $this->optionRightDelimiter = $this->config['optionRightDelimiter'] ?? $this->optionRightDelimiter;
        $this->defaultOptionDelimiter = $this->config['defaultOptionDelimiter'] ?? $this->defaultOptionDelimiter;

        // add all of the rules
        $this->addRules($this->config['rules']);

        $this->notation = new Notation($this->notationDelimiter);

        // reset class
        $this->reset();
    }

    /**
     * Get one of the delimiters or all of them
     *
     * @param string $needle
     * @return string|array
     */
    public function getDelimiters(string $needle = ''): string|array
    {
        $delimiters = [
            'left' => $this->optionLeftDelimiter,
            'right' => $this->optionRightDelimiter,
            'options' => $this->defaultOptionDelimiter,
            'rule' => $this->ruleDelimiter,
        ];

        return $delimiters[$needle] ?? $delimiters;
    }

    /**
     * Reset the class to a blank state
     *
     * @return Validate
     */
    public function reset(): self
    {
        $this->errors = [];

        // the current value being worked on.
        // this is passed into rules by reference
        $this->currentInput = null;

        // the current rules error msg, rule, and any option(s)
        $this->currentErrorMsg = '';
        $this->currentRule = '';
        $this->currentOptions = '';

        // class wide property to indicate wether to continue processing rules
        $this->stopProcessing = false;

        return $this;
    }

    /**
     * Add a validation rule
     *
     * @param string $name
     * @param string $classMethod
     * @return Validate
     */
    public function addRule(string $name, string $classMethod): self
    {
        // normalize the name to lowercase
        $this->rules[strtolower($name)] = $classMethod;

        return $this;
    }

    /**
     * Add multiple validation rules at once
     *
     * @param array $rules
     * @return Validate
     */
    public function addRules(array $rules): self
    {
        // add an array of rules
        foreach ($rules as $name => $classMethod) {
            $this->addRule($name, $classMethod);
        }

        return $this;
    }

    /**
     * Single request filter
     * $clean = $filter->request('name','readable');
     * $clean = $filter->request('email',['email','required']);
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     *
     * @param mixed $input
     * @param array|string $rules
     * @param string|null $human
     * @return Validate
     * @throws ValidationFailed
     */
    public function input(mixed $input, array|string $rules, ?string $human = null): self
    {
        // reset class
        $this->reset();

        if (is_string($rules)) {
            $rules = explode($this->ruleDelimiter, $rules);
        }

        // save raw input and create a variable so we can pass by reference
        $this->currentInput = $input;

        if (is_array($this->currentInput) || is_object($this->currentInput)) {
            $this->processArrayOrObject($rules);
        } else {
            $this->processSingleValue($rules, $human);
        }

        return $this;
    }

    /**
     * Add an error to the list
     *
     * @param string $errorMsg
     * @param string $human
     * @param string $options
     * @param string $rule
     * @param string $input
     * @return Validate
     */
    public function addError(string $errorMsg, string $human = '', string $options = '', string $rule = '', string $input = ''): self
    {
        // There are %d monkeys in the %s (in order)
        // The %2$s contains %1$d monkeys (arg by number)
        // https://www.php.net/manual/en/function.sprintf.php

        $this->errors[] = new ValidationError(sprintf($errorMsg, $human, $options, $rule, $input), $this->currentKey, $errorMsg, $human, $options, $rule, $input);

        return $this;
    }

    /**
     * Get the current input value
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->currentInput;
    }

    /**
     * Get all of the current input values (array or object)
     *
     * @return mixed
     */
    public function values(): mixed
    {
        return $this->currentInput;
    }

    /**
     * Set the current input value
     *
     * @param mixed $input
     * @return Validate
     */
    public function setCurrentInput(mixed $input): self
    {
        $this->currentInput = $input;

        return $this;
    }

    /**
     * Stop processing any further rules for the current input value
     *
     * @return Validate
     */
    public function stopProcessing(): self
    {
        $this->stopProcessing = true;

        return $this;
    }

    /**
     * Request filter
     * $clean = $filter->request([
     *   'name' => 'readable',
     *  'email' => 'email',
     * ]);
     * or
     * $clean = $filter->request([
     *  'name' => ['string','maxlength[20]'],
     *  'age' => ['int','min[0]'],
     *  'email' => ['email','required'],
     * ]);
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     *
     * @param bool $bool
     * @return Validate
     */
    public function throwExceptionOnFailure(bool $bool = true): self
    {
        $this->throwExceptionOnFailure = $bool;

        return $this;
    }

    /**
     * Does the current validation have any errors?
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Does the current validation have any errors?
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->hasError();
    }

    /**
     * Does the current validation have no errors?
     *
     * @return bool
     */
    public function hasNoErrors(): bool
    {
        return !$this->hasErrors();
    }

    /**
     * Get the current errors
     *
     * @param bool $raw
     * @return array
     */
    public function errors(bool $raw = false): array
    {
        return ($raw) ? $this->errors : $this->errorsText();
    }

    /**
     * Get the first error only
     *
     * @return string
     */
    public function error(): string
    {
        $error = '';

        if (isset($this->errors[0])) {
            $error = $this->errors[0]->text;
        }

        return $error;
    }

    /**
     * Protected
     */

    /**
     * Get just the text of the errors
     *
     * @return array
     */
    protected function errorsText(): array
    {
        $errorsText = [];

        foreach ($this->errors as $error) {
            $errorsText[] = $error->text;
        }

        return $errorsText;
    }

    /**
     * Throw an exception if we have errors and the flag is set
     *
     * @return Validate
     * @throws ValidationFailed
     */
    protected function throwException(): self
    {
        if ($this->throwExceptionOnFailure && $this->hasErrors()) {
            // throw validation exception
            throw new ValidationFailed(implode(PHP_EOL, $this->errorsText()), $this->exceptionCode, null, $this->errors);
        }

        return $this;
    }

   /**
    * Process a single value with 1 or more rules

    * @param array $rules
    * @param null|string $human
    * @return Validate
    * @throws ValidationFailed
    */
    protected function processSingleValue(array $rules, ?string $human = null): self
    {
        // validate a single value against 1 or more rules
        // arg1 passed by reference
        $this->currentInput = $this->validateSingleValueMultipleRules($this->currentInput, $rules, $this->makeHumanLookNice($human, 'Input'));

        // throw an exception on error if necessary
        return $this->throwException();
    }

    /**
     * Process an array or object with multiple keys and rules
     *
     * @param array $ruleSet
     * @return Validate
     * @throws ValidationFailed
     */
    protected function processArrayOrObject(array $ruleSet): self
    {
        foreach ($ruleSet as $key => $rules) {
            $this->currentKey = (string)$key;

            if (is_array($rules)) {
                // get human first before rules is overwritten
                $human = $rules[1] ?? '';
                $rules = $rules[0] ?? '';
            } else {
                $human = $this->makeHumanLookNice(null, (string)$key);
            }

            if (!is_array($rules)) {
                $rules = explode($this->ruleDelimiter, $rules);
            }

            if ($this->notationDelimiter == '') {
                // no dot notation delimiter in effect
                $value = $this->currentInput[$key] ?? '';

                $this->currentInput[$key] = $this->validateSingleValueMultipleRules($value, $rules, $human);
            } else {
                $value = $this->notation->get($this->currentInput, $key);

                $value = $this->validateSingleValueMultipleRules($value, $rules, $human);

                $this->notation->set($this->currentInput, $key, $value);
            }

            $this->currentKey = '';
        }

        return $this->throwException();
    }

    /**
     * Validate a single value against multiple rules
     *
     * @param mixed $input
     * @param array $rules
     * @param string|null $human
     * @return mixed
     */
    protected function validateSingleValueMultipleRules(mixed $input, array $rules, string $human = null): mixed
    {
        // continue processing rules
        $this->stopProcessing = false;

        foreach ($rules as $rule) {
            // input passed by reference
            $input = $this->validateSingleValueSingleRule($input, $rule, $human);

            // if they trigger the stop processing flag then break from the foreach loop
            if ($this->stopProcessing) {
                break;
            }
        }

        return $input;
    }

    /**
     * Validate a single value against a single rule
     * This WILL throw an error on fail
     *
     * @param mixed $input
     * @param string $rule
     * @param null|string $human
     * @return mixed
     */
    protected function validateSingleValueSingleRule(mixed $input, string $rule, ?string $human = ''): mixed
    {
        // copy it
        $previousValue = $input;

        try {
            // try to process the current value if it throws an exception current value isn't changed
            // input is passed by reference
            $this->callRule($input, $rule);
        } catch (RuleFailed $e) {
            // if the rule or filter threw an error it is captured here
            $this->addError($e->getMessage(), $human, $this->currentOptions, $this->currentRule, (string)$previousValue);

            // stop on first error
            $this->stopProcessing = true;
        }

        return $input;
    }

   /**
    * Call the rule class and method

    * @param mixed &$value
    * @param string $rule
    * @return void
    */
    protected function callRule(mixed &$value, string $rule): void
    {
        // default error
        $this->currentErrorMsg = $this->defaultErrorMsg;

        if (!empty($rule)) {
            $options = '';

            $regex = ';(?<rule>.*)' . preg_quote($this->optionLeftDelimiter) . '(?<options>.*)' . preg_quote($this->optionRightDelimiter) . ';';

            if (preg_match($regex, $rule, $matches, 0, 0)) {
                $rule = $matches['rule'];
                $options = $matches['options'];
            }

            $this->currentRule = $rule;
            $this->currentOptions = $this->makeOptionsLookNice($options);

            // normalize rule name
            $rule = strtolower($rule);

            if (isset($this->rules[$rule])) {
                list($class, $method) = explode('::', $this->rules[$rule], 2);
            } else {
                throw new RuleNotFound('Unknown Rule or Filter "' . $rule . '".');
            }

            // make instance - this should autoload
            if (class_exists($class, true)) {
                $instance = new $class($value, $options, $this->config, $this);

                if (!$instance instanceof RuleAbstract) {
                    throw new InvalidValue('"' . $class . '" is not an instance of RuleAbstract.');
                }
            } else {
                throw new RuleNotFound('Unknown Class "' . $class . '".');
            }

            if (method_exists($instance, $method)) {
                // throws an error on fail this is captured in validateSingleValueSingleRule()
                $instance->$method();
            } else {
                throw new RuleNotFound('Unknown Method "' . $method . '" on Class "' . $class . '".');
            }
        }
    }

    /**
     * Make a human readable field name if one isn't given
     *
     * @param null|string $human
     * @param string $key
     * @return string
     */
    protected function makeHumanLookNice(?string $human, string $key): string
    {
        // do we have a human readable field name? if not then try to make one
        $key = empty($key) ? 'Input' : $key;

        return $human ?? strtolower(str_replace('_', ' ', $key));
    }

    /**
     * Make options look nice for error messages
     *
     * @param null|string $option
     * @param string $delimiter
     * @return string
     */
    protected function makeOptionsLookNice(?string $option, string $delimiter = ','): string
    {
        $nice = '';

        // try to format the options into something human readable incase they need this in there error message
        if (!empty($option)) {
            if (strpos($option, $delimiter) !== false) {
                $nice = str_replace($delimiter, $delimiter . ' ', $option);

                if (($pos = strrpos($this->currentOptions, $delimiter . ' ')) !== false) {
                    $nice = substr_replace($this->currentOptions, ' or ', $pos, 2);
                }
            } else {
                $nice = $option;
            }
        }

        return $nice;
    }

    /**
     * Send in NULL if you want to turn "off" dot notation "drill down" into your input
     *
     * Send in something else if for some reason you would like to
     * use another delimiter to indicate how to drill down to the next level
     */
    public function changeNotationDelimiter(string $delimiter): self
    {
        $this->notationDelimiter = $delimiter;

        $this->notation->changeDelimiter($delimiter);

        return $this;
    }

    /**
     * Disable dot notation "drill down" into your input
     *
     * @return Validate
     */
    public function disableNotation(): self
    {
        return $this->changeNotationDelimiter('');
    }
}
