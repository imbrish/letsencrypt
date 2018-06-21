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
     * Command aliases.
     * 
     * @var array
     */
    public static $aliases = [];

    /**
     * Default arguments.
     * 
     * @var array
     */
    public static $defaults = [];

    /**
     * Result of last execution.
     * 
     * @var int
     */
    public static $result;

    /**
     * Output of last execution.
     * 
     * @var string
     */
    public static $output;

    /**
     * Last executed command.
     * 
     * @var string
     */
    public static $last;

    /**
     * Flat array of commands parts.
     * 
     * @var array
     */
    protected $parts = [];

    /**
     * Execute command.
     *
     * @param string $cmd
     * @param array $args
     *
     * @return mixed
     */
    public static function exec($cmd, $args = [])
    {
        $command = new static($cmd, $args);

        return $command();
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
        // initialize command from alias or as given
        $this->parts = [
            PHP_BINARY,
            array_key_exists($cmd, static::$aliases) ? static::$aliases[$cmd] : $cmd,
        ];

        // attach the default arguments
        if (array_key_exists($cmd, static::$defaults)) {
            foreach (static::$defaults[$cmd] as $key => $value) {
                if (! is_int($key)) {
                    $args[$key] = $value;
                }
                else if (! in_array($value, $args)) {
                    $args[] = $value;
                }
            }
        }

        // convert arguments into command parts
        foreach ($args as $key => $value) {
            if (! is_int($key)) {
                $this->parts[] = $key;
            }

            $this->parts[] = $value;
        }
    }

    /**
     * Execute command.
     *
     * @return mixed
     */
    public function __invoke()
    {
        // escape command parts where necessary
        $parts = array_map(function ($part) {
            return strpos($part, ' ') === false ? $part : escapeshellarg($part);
        }, $this->parts);

        // save and show what command is executed
        static::$last = implode(' ', $parts);

        static::$climate->comment(static::$last);

        // temporary redirect error log to fetch its output after script execution
        file_put_contents($logPath = __DIR__ . '/../logs.txt', '');

        $logPath = realpath($logPath);

        array_splice($parts, 1, 0, '-d errorLog=' . escapeshellarg($logPath));

        // tunnel error output to the standard output
        $parts[] = '2>&1';

        // run command storing its output and return code
        exec(implode(' ', $parts), $output, $code);

        // get and erase temporary error log, remove dates from the logged errors
        $errorLog = file_get_contents($logPath);
        $errorLog = preg_replace('/^\[.+?\] /m', '', $errorLog);

        unlink($logPath);

        // save and show command output together with collected error logs
        static::$output = rtrim(implode(PHP_EOL, $output)) . PHP_EOL . $errorLog;
        static::$output = preg_replace('/^[\t ]+/m', '', trim(static::$output));

        if (static::$output) {
            static::$climate->out(static::$output);
        }

        // save and return last code
        return static::$result = $code;
    }
}
