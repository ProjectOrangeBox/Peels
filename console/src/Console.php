<?php

declare(strict_types=1);

namespace peels\console;

use Exception;
use peels\console\BitWise;
use peels\console\ConsoleInterface;
use orange\framework\base\Singleton;
use peels\console\exceptions\Console as ConsoleException;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;

class Console extends Singleton implements ConsoleInterface
{
    use ConfigurationTrait;

    protected $ansiCodes = [
        'off'               => 0,

        'bold'              => 1,
        'dim'               => 2,
        'italic'            => 3,
        'underline'         => 4,
        'blink'             => 5,
        'inverse'           => 7,
        'hidden'            => 8,

        'bold off'          => 21,
        'dim off'           => 22,
        'italic off'        => 23,
        'underline off'     => 24,
        'blink off'         => 25,
        'inverse off'       => 27,
        'hidden off'        => 28,

        'black'             => 30,
        'red'               => 31,
        'green'             => 32,
        'yellow'            => 33,
        'blue'              => 34,
        'magenta'           => 35,
        'cyan'              => 36,
        'white'             => 37,
        'default'           => 39,

        'black bg'          => 40,
        'red bg'            => 41,
        'green bg'          => 42,
        'yellow bg'         => 43,
        'blue bg'           => 44,
        'magenta bg'        => 45,
        'cyan bg'           => 46,
        'white bg'          => 47,
        'default bg'        => 49,

        'bright black'      => 90,
        'bright red'        => 91,
        'bright green'      => 92,
        'bright yellow'     => 93,
        'bright blue'       => 94,
        'bright magenta'    => 95,
        'bright cyan'       => 96,
        'bright white'      => 97,
        'bright default'    => 99,

        'bright black bg'   => 100,
        'bright red bg'     => 101,
        'bright green bg'   => 102,
        'bright yellow bg'  => 103,
        'bright blue bg'    => 104,
        'bright magenta bg' => 105,
        'bright cyan bg'    => 106,
        'bright white bg'   => 107,
        'bright default bg' => 109,
    ];

    protected array $named = [
        'always'    => ['icon' => '', 'stream' => \STDOUT, 'color' => ''],
        'alert'     => ['icon' => '➤ ', 'stream' => \STDOUT, 'color' => '<bright yellow>'],
        'critical'  => ['icon' => '✘ ', 'stream' => \STDERR, 'color' => '<bright red>'],
        'debug'     => ['icon' => '❖ ', 'stream' => \STDOUT, 'color' => '<bright green>'],
        'emergency' => ['icon' => '✘ ', 'stream' => \STDERR, 'color' => '<bright magenta>'],
        'error'     => ['icon' => '✘ ', 'stream' => \STDERR, 'color' => '<bright red>'],
        'info'      => ['icon' => '', 'stream' => \STDOUT, 'color' => ''],
        'notice'    => ['icon' => '➤ ', 'stream' => \STDOUT, 'color' => '<bright yellow>'],
        'warning'   => ['icon' => '➤ ', 'stream' => \STDOUT, 'color' => '<bright yellow>'],
    ];

    protected array $defaultLevels = [
        'bell' => 'info',
        'line' => 'info',
        'clear' => 'info',
        'linefeed' => 'info',
        'table' => 'info',
    ];

    protected string $listFormat = '<off>[<cyan>%key%<off>] %value%';
    protected string $lf = "\n";
    protected bool $color = true;
    protected string $bell = '';

    protected array $argv = [];
    protected int $argc = 0;

    protected BitWise $verbose;
    protected string $verboseChar = 'v';
    protected string $defaultVerbose = 'info';
    protected string $defaultUpperCaseVerbose = 'everything';

    // unit testing storage
    protected bool $simulate = false;
    protected int $simulatedWidth = 80;
    protected string $stdin = '';
    protected string $stderr = '';
    protected string $stdout = '';

    protected function __construct(array $config, InputInterface $input)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->lf = $this->config['Linefeed Character'] ?? $this->lf;
        $this->simulate = $this->config['simulate'] ?? $this->simulate;
        $this->listFormat = $this->config['List Format'] ?? $this->listFormat;
        $this->color = $this->config['color'] ?? $this->color;

        if (isset($this->config['ANSI Codes'])) {
            $this->ansiCodes = array_replace($this->ansiCodes, $this->config['ANSI Codes']);
        }

        $this->named = $this->config['named'] ?? $this->named;

        $this->verboseChar = $this->config['verbose char'] ?? $this->verboseChar;
        // setup bitwise with our named values
        $this->verbose = new BitWise(['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'debug']);
        $this->verboseChar = $this->config['verbose char'] ?? $this->verboseChar;
        $this->defaultVerbose = $this->config['default verbose'] ?? $this->defaultVerbose;
        $this->defaultUpperCaseVerbose = $this->config['default uppercase verbose'] ?? $this->defaultUpperCaseVerbose;

        $this->argv = $input->server('argv', []);
        $this->argc = $input->server('argc', 0);

        $this->bell = $this->config['bell'] ?? chr(7);
    }

    public function verboseAdd(): self
    {
        $args = func_get_args();

        $this->verbose->turnOn($args);

        return $this;
    }

    public function VerboseRemove(): self
    {
        $args = func_get_args();

        $this->verbose->turnOff($args);

        return $this;
    }

    public function verboseReset(): self
    {
        $this->verbose->reset();

        return $this;
    }

    /**
     * auto detect the verbose level
     * command.php -vDebug (debug)
     * command.php -vDebug -vInfo (debug & info)
     * command.php -V (everything)
     */
    public function detectVerboseLevel(?string $char = null): void
    {
        $char = $char ?? $this->verboseChar;

        foreach ($this->argv as $arg) {
            if ($arg == '-' . strtoupper($char)) {
                $this->verboseAdd($this->defaultUpperCaseVerbose);
            } elseif ($arg == '-' . $char) {
                $this->verboseAdd($this->defaultVerbose);
            } elseif (substr($arg, 0, 2) == '-' . $char) {
                $bit = substr($arg, 2);

                if ($this->verbose->hasBit($bit)) {
                    $this->verboseAdd($bit);
                }
            }
        }
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (!isset($this->named[$name])) {
            throw new ConsoleException('Unknown console method "' . $name . '".');
        }

        $string = $arguments[0] ?? '';
        $linefeed = $arguments[1] ?? true;

        $this->write($this->formatOutput($this->named[$name]['color'] . $this->named[$name]['icon'] . $string, (bool)$linefeed), $name, $this->named[$name]['stream']);

        return $this;
    }

    public function bell(int $times = 1, ?string $level = null): self
    {
        $level = $level ?? $this->defaultLevels['bell'];

        $this->write(str_repeat($this->bell, $times), $level, \STDOUT);

        return $this;
    }

    public function line(?int $length = null, string $char = '-', ?string $level = null): self
    {
        $level = $level ?? $this->defaultLevels['line'];

        if ($length == null && $this->simulate) {
            // fixed amount in simulate mode
            $times = $this->simulatedWidth;
        } else {
            $times = $length ?? (int)$this->system('tput cols');
        }

        $times = (int)floor($times / strlen($char));

        $this->write(str_repeat($char, $times) . $this->lf, $level, \STDOUT);

        return $this;
    }

    public function clear(?string $level = null): self
    {
        $level = $level ?? $this->defaultLevels['clear'];

        if ($this->simulate) {
            // if simulating "clear" the output
            $this->stderr = '';
            $this->stdout = '';
        } elseif ($this->verbose->isSet($level)) {
            $this->system('clear');
        }

        return $this;
    }

    public function linefeed(int $times = 1, ?string $level = null): self
    {
        $level = $level ?? $this->defaultLevels['linefeed'];

        return $this->write(str_repeat($this->lf, $times), $level, \STDOUT);
    }

    public function table(array $table, ?string $level = null): self
    {
        $level = $level ?? $this->defaultLevels['table'];

        // get max column size
        $columnsMaxWidth = [];

        foreach ($table as $rowIndex => $row) {
            foreach ($row as $columnIndex => $column) {
                if (!isset($columnsMaxWidth[$columnIndex])) {
                    $columnsMaxWidth[$columnIndex] = 0;
                }

                $columnsMaxWidth[$columnIndex] = max($columnsMaxWidth[$columnIndex], strlen((string)$column) + 1);
            }
        }

        $totalWidth = 0;

        $masks = [];

        foreach ($table as $rowIndex => $row) {
            $m = [];
            foreach ($row as $columnIndex => $column) {
                $width = $columnsMaxWidth[$columnIndex];

                $m[] = ' %-' . $width . '.' . $width . 's ';

                if ($rowIndex == 0) {
                    $totalWidth = $totalWidth + $width + 2;
                }
            }

            $masks[$rowIndex] = '|' . implode('|', $m) . '|';
        }

        $totalWidth = $totalWidth  + 4;

        $this->line($totalWidth, '-', $level);

        foreach ($table as $rowIndex => $row) {
            array_unshift($row, $masks[$rowIndex]);

            ob_start();

            call_user_func_array('printf', $row);

            $this->$level(trim(ob_get_clean()), $level);

            if ($rowIndex == 0) {
                $this->line($totalWidth, '-', $level);
            }
        }

        $this->line($totalWidth, '-', $level);

        return $this;
    }

    public function list(array $list): self
    {
        foreach ($list as $key => $value) {
            $this->always(str_replace(['%key%', '%value%'], [$key, $value], $this->listFormat));
        }

        return $this;
    }

    /* get input until return is pressed */

    public function getLine(?string $prompt = null): string
    {
        if ($prompt) {
            $this->always($prompt);
        }

        // if in simulate send back std in
        return ($this->simulate) ? $this->stdin : rtrim(fgets(\STDIN), $this->lf);
    }

    public function getLineOneOf(?string $prompt = null, array $options = []): string
    {
        do {
            $input = $this->getLine($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        return $input;
    }

    /**
     * single character (no return needed)
     *
     * This method has a extra exit for simulation mode
     */
    public function get(?string $prompt = null): string
    {
        if ($prompt) {
            $this->always($prompt);
        }

        // if in simulate send back stdin
        if ($this->simulate) {
            // BAIL NOW - multiple exits
            return $this->stdin;
        }

        // setup console no buffer
        $this->system('stty -icanon');

        while ($char = fread(\STDIN, 1)) {
            return $char;
        }

        // just incase we slip through to here
        return '';
    }

    public function getOneOf(?string $prompt = null, array $options = []): string
    {
        do {
            $input = $this->get($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        $this->linefeed(1);

        return $input;
    }

    public function exit(int $exitLevel = 0)
    {
        if ($this->simulate) {
            throw new ConsoleException('Exception thrown with exit level ' . $exitLevel);
        } else {
            exit($exitLevel);
        }
    }

    /* Arguments */

    public function minimumArguments(int $num, ?string $error = null): self
    {
        if ($this->argc < ($num + 1)) {
            $error = $error ?? 'Please provide ' . $num . ' arguments';

            $this->error($error)->exit(1);
        }

        return $this;
    }

    public function getArgumentExists(string $match): bool
    {
        $found = false;

        foreach ($this->argv as $arg) {
            if ($arg == $match) {
                $found = true;

                break;
            }
        }

        return $found;
    }

    public function getArgument(int $num, ?string $error = null): string
    {
        if (!isset($this->argv[$num])) {
            if (!$error) {
                $error = 'Could not locate a Argument ' . $num;
            }

            $this->error($error)->exit(1);
        }

        return $this->argv[$num];
    }

    public function getLastArgument(): string
    {
        $last = '';

        if ($this->argc > 0) {
            $last = end($this->argv);
        }

        return $last;
    }

    public function getArgumentByOption(string $match, ?string $error = null): string
    {
        if (!$error) {
            $error = 'Could not locate a option for ' . $match;
        }

        foreach ($this->argv as $key => $value) {
            if ($value == $match) {
                $next = $key + 1;

                if (!isset($this->argv[$next])) {
                    $this->error($error)->exit(1);
                }

                return $this->argv[$next];
            }
        }

        $this->error($error)->exit(1);

        return '';
    }

    public function formatOutput(string $string, bool $linefeed = true): string
    {
        $string = $this->stripTags($string);

        $turnOff = '';

        // find all the <tags>
        preg_match_all('/<([^>]*)>/i', $string, $tags, PREG_SET_ORDER, 0);

        foreach ($tags as $tag) {
            $colorsEscaped = '';

            // apply color escape codes
            if (!isset($this->ansiCodes[$tag[1]])) {
                $this->error('Could not find tag "' . $tag[1] . '"')->exit(1);
            }

            foreach (explode(',', (string)$this->ansiCodes[$tag[1]]) as $colorEscapeCode) {
                $colorsEscaped .= "\033[" . $colorEscapeCode . "m";
            }

            $string = str_replace($tag[0], $colorsEscaped, $string);

            $turnOff = "\033[" . $this->ansiCodes['off'] . "m";
        }

        return $string . $turnOff . (($linefeed) ? $this->lf : '');
    }

    /* protected */

    /**
     * strip all tags if we are in no color mode
     */
    protected function stripTags(string $string): string
    {
        // quick find and replace for all linefeeds
        $string = str_replace('<lf>', $this->lf, $string);

        if (!$this->color) {
            preg_match_all('/<([^>]*)>/i', $string, $tags, PREG_SET_ORDER, 0);

            foreach ($tags as $tag) {
                $string = str_replace($tag[0], '', $string);
            }
        }

        return $string;
    }

    protected function oneOf(string $input, array $oneOf, ?string $error = null): bool
    {
        $success = true;
        $shownError = '';

        if (empty(trim($input))) {
            $shownError = $error ?? 'Please select an option.';
        } elseif (!in_array($input, $oneOf)) {
            $shownError = $error ?? 'Your input did not match an option.';
            $shownError = $this->lf . $shownError;
        }

        if (!empty($shownError)) {
            $this->linefeed(0)->always($shownError);
            $success = false;
        }

        return $success;
    }

    protected function write(string $string, string $level, $stream): self
    {
        if ($this->verbose->isSet($level)) {
            if ($this->simulate) {
                if ($stream == \STDERR) {
                    $this->stderr .= $string;
                } else {
                    $this->stdout .= $string;
                }
            } else {
                fwrite($stream, $string);
            }
        }

        return $this;
    }

    protected function validateArgument($arguments, $index, $default, $function)
    {
        $typeMap = [
            'is_string' => 'string',
            'is_int' => 'integer',
            'is_float' => 'floating',
            'is_bool' => 'boolean',
            'is_array' => 'array',
        ];

        $type = $typeMap[$function];

        if (!isset($arguments[$index])) {
            $return = $default;
        } else {
            if (!$function($arguments[$index])) {
                throw new \Exception('Argument ' . ($index + 1) . ' must be ' . $type . '.');
            }

            $return = $arguments[$index];
        }

        return $return;
    }

    protected function system(string $command): string
    {
        $resultCode = 0;
        $output = [];

        exec($command, $output, $resultCode);

        return (empty($output)) ? '' : $output[0];
    }
}
