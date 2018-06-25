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
    public static $aliases;

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
    public static $output;

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
    protected $parts;

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
        // initialize command parts from alias or as given
        if (array_key_exists($cmd, static::$aliases)) {
            $cmd = (array) static::$aliases[$cmd];
        }

        $parts = array_merge((array) $cmd, $args);

        $this->cmd = reset($parts);

        // convert keyed parts into options and escape where necessary
        foreach ($parts as $key => $value) {
            if (strpos($value, ' ') !== false) {
                $value = escapeshellarg($value);
            }

            if (! is_int($key)) {
                $value = $key . '=' . $value;
            }

            $this->parts[] = $value;
        }
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

        $this->$method($this->parts);

        return static::$result;
    }

    /**
     * Set last command.
     *
     * @param string $command
     *
     * @return void
     */
    protected function setLastCommand($command)
    {
        static::$last = $command;

        static::$climate->comment($command);
    }

    /**
     * Set last result and output.
     *
     * @param int $result
     * @param string $output
     *
     * @return void
     */
    protected function setLastResult($result, $output)
    {
        static::$result = $result;

        $output = preg_replace('/^[\t ]*[\n\r]*/m', '', trim($output));

        static::$output = $output;

        if ($output) {
            static::$climate->out($output);
        }
    }

    /**
     * Handle generic command.
     *
     * @param array $parts
     *
     * @return void
     */
    protected function handle($parts)
    {
        $this->setLastCommand(
            $command = implode(' ', $parts)
        );

        exec($command, $output, $result);

        $this->setLastResult(
            $result, implode(PHP_EOL, $output)
        );
    }

    /**
     * Handle PHP command.
     *
     * @param array $parts
     *
     * @return void
     */
    protected function handlePHP($parts)
    {
        $this->setLastCommand(implode(' ', $parts));

        // redirect PHP error log to fetch errors after execution
        $logPath = static::$config['storage'] . '/error.log';

        array_splice($parts, 1, 0, [
            '-d',
            'errorLog=' . escapeshellarg($logPath),
        ]);

        // tunnel error output to the standard output
        $parts[] = '2>&1';

        // run command, store its output and return code
        exec(implode(' ', $parts), $output, $result);

        // get and erase temporary error log, remove dates from the logged errors
        if (file_exists($logPath)) {
            $errorLog = preg_replace('/^\[.+?\] /m', '', file_get_contents($logPath));

            unlink($logPath);
        }
        else {
            $errorLog = '';
        }

        // merge command output and collected error logs
        $this->setLastResult(
            $result, implode(PHP_EOL, $output) . PHP_EOL . $errorLog
        );
    }

    /**
     * Handle UAPI command.
     *
     * @param array $parts
     *
     * @return void
     */
    protected function handleUAPI($parts)
    {
        // add default arguments, when used as root we need to specify cPanel user
        array_splice($parts, 1, 0, array_filter([
            '--output=json',
            posix_getuid() == 0 ? '--user=' . escapeshellarg(static::$config['user']) : null,
        ]));

        // obfuscate certificate data for cleaner output
        $this->setLastCommand(
            preg_replace('/(-----BEGIN\+[^ ]+)/', '***', implode(' ', $parts))
        );

        // run command and parse response to determine result code and output
        $response = shell_exec(implode(' ', $parts));

        if ($response = json_decode($response, true)) {
            $messages = array_merge(
                $response['result']['errors'] ?: [],
                $response['result']['messages'] ?: []
            );

            $this->setLastResult(
                $response['result']['status'] ? 0 : 1,
                $messages ? implode(PHP_EOL, $messages) : 'The UAPI call failed for an unknown reason.'
            );
        }
        else {
            $this->setLastResult(255, 'The UAPI call did not return a valid response.');
        }
    }
}
