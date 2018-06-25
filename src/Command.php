<?php 

namespace Imbrish\LetsEncrypt;

class Command {
    /**
     * The CLImate instance.
     * 
     * @var \League\CLImate\CLImate
     */
    public static $climate;

    /**
     * The configuration array.
     * 
     * @var array
     */
    public static $config;

    /**
     * Command aliases.
     * 
     * @var array
     */
    public static $aliases = [];

    /**
     * Last executed command.
     * 
     * @var string
     */
    public static $last;

    /**
     * Result of last executed command.
     * 
     * @var int
     */
    public static $result;

    /**
     * Output of last executed command.
     * 
     * @var string
     */
    public static $output = '';

    /**
     * Non escaped command.
     * 
     * @var string
     */
    protected $cmd;

    /**
     * Flat array of command parts.
     * 
     * @var array
     */
    protected $parts = [];

    /**
     * Path for error logs.
     * 
     * @var string
     */
    protected $errorLog;

    /**
     * Execute command and return result code.
     *
     * @param string $cmd
     * @param array $args
     *
     * @return int
     */
    public static function exec($cmd, $args = [])
    {
        return call_user_func(new static($cmd, $args));
    }

    /**
     * Construct new command instance.
     *
     * @param string $cmd
     * @param array $args
     *
     * @return void
     */
    public function __construct($cmd, $args = [])
    {
        // clear last error logs, command, result and output
        $this->errorLog = static::$config['storage'] . '/error.log';

        if (file_exists($this->errorLog)) {
            unlink($this->errorLog);
        }

        static::$last = null;
        static::$result = null;
        static::$output = '';

        // initialize command parts from alias or as given
        if (array_key_exists($cmd, static::$aliases)) {
            $cmd = (array) static::$aliases[$cmd];
        }

        $parts = array_merge((array) $cmd, $args);

        $this->cmd = reset($parts);

        $this->insertParts($this->parts, $parts);
    }

    /**
     * Print and remember last command.
     *
     * @param string $command
     *
     * @return void
     */
    protected function printCommand($command)
    {
        static::$last = $command;

        if (static::$climate->arguments->defined('verbose')) {
            static::$climate->comment($command);
        }
    }

    /**
     * Print and remember output.
     *
     * @param string $output
     *
     * @return void
     */
    protected function printOutput($output)
    {
        // remove leading whitespace from every line and all empty lines
        $output = preg_replace('/^[\t ]*[\n\r]*/m', '', $output);

        $output = trim($output);

        if ($output) {
            static::$output .= $output . PHP_EOL;

            static::$climate->out($output);
        }
    }

    /**
     * Print errors and clean error log.
     *
     * @return void
     */
    protected function printErrors()
    {
        if (! file_exists($this->errorLog)) {
            return;
        }

        // remove timestamps from the logged errors
        $errors = preg_replace('/^\[.+?\] /m', '', file_get_contents($this->errorLog));

        if (static::$climate->arguments->defined('verbose')) {
            $this->printOutput($errors);
        }

        unlink($this->errorLog);
    }

    /**
     * Insert parts at given position or at the end.
     *
     * @return array &$parts
     * @return int $pos
     * @return array $new
     *
     * @return void
     */
    protected function insertParts(&$parts, $pos, $new = null)
    {
        if (is_array($pos)) {
            list($pos, $new) = [count($pos), $pos];
        }

        // convert keyed parts into options and escape where necessary
        $parsed = [];

        foreach ($new as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (strpos($value, ' ') !== false) {
                $value = escapeshellarg($value);
            }

            if (! is_int($key)) {
                $value = $key . '=' . $value;
            }

            $parsed[] = $value;
        }

        array_splice($parts, $pos, 0, $parsed);
    }

    /**
     * Execute command and return result code.
     *
     * @return int
     */
    public function __invoke()
    {
        if ($this->cmd === PHP_BINARY) {
            $method = 'handlePHP';
        }
        else if ($this->cmd === UAPI_BINARY) {
            $method = 'handleUAPI';
        }
        else {
            $method = 'handle';
        }

        return static::$result = $this->$method($this->parts);
    }

    /**
     * Handle generic command.
     *
     * @param array $parts
     *
     * @return int
     */
    protected function handle($parts)
    {
        $this->printCommand($command = implode(' ', $parts));

        exec($command, $output, $result);

        $this->printOutput(implode(PHP_EOL, $output));

        return $result;
    }

    /**
     * Handle PHP command.
     *
     * @param array $parts
     *
     * @return int
     */
    protected function handlePHP($parts)
    {
        // print command before adding debug
        $this->printCommand(implode(' ', $parts));

        // redirect errors to fetch after execution
        $this->insertParts($parts, 1, [
            '-d',
            'errorLog' => $this->errorLog,
        ]);

        $this->insertParts($parts, [
            '2>',
            $this->errorLog,
        ]);

        // run command, print errors and output
        exec(implode(' ', $parts), $output, $result);

        $this->printErrors();

        $this->printOutput(implode(PHP_EOL, $output));

        return $result;
    }

    /**
     * Handle UAPI command.
     *
     * @param array $parts
     *
     * @return int
     */
    protected function handleUAPI($parts)
    {
        // add default arguments, when used as root we need to specify cPanel user
        $this->insertParts($parts, 1, [
            '--output' => 'json',
            '--user' => posix_getuid() == 0 ? static::$config['user'] : null,
        ]);

        // obfuscate certificate data for cleaner output
        $this->printCommand(
            preg_replace('/(-----BEGIN\+[^ ]+)/', '***', implode(' ', $parts))
        );

        // redirect errors to fetch after execution
        $this->insertParts($parts, [
            '2>',
            $this->errorLog,
        ]);

        // run command and parse response to determine result code and output
        $response = shell_exec(implode(' ', $parts));

        $this->printErrors();

        if (! $response = json_decode($response, true)) {
            $this->printOutput('The UAPI call did not return a valid response.');
            return 255;
        }

        $messages = convertQuotes(implode(PHP_EOL, array_merge(
            $response['result']['errors'] ?: [],
            $response['result']['messages'] ?: []
        )));

        $this->printOutput($messages ?: 'The UAPI call failed for an unknown reason.');

        return $response['result']['status'] ? 0 : 1;
    }
}
