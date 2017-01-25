<?php 

namespace Imbrish\LetsEncrypt;

class Command {
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
    public static function exec($cmd, $args)
    {
        $instance = new static($cmd, $args);

        return $instance->__exec();
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
        $this->parts = [
            PHP_BINARY,
            array_key_exists($cmd, static::$aliases) ? static::$aliases[$cmd] : $cmd,
        ];

        if (array_key_exists($cmd, static::$defaults)) {
            foreach (static::$defaults[$cmd] as $key => $value) {
                if (! is_int($key)) {
                    $args[$key] = $value;
                }
                else if (! in_array($value, $args)) {
                    $args[$value];
                }
            }
        }

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
    public function __exec()
    {
        $cmd = implode(' ', array_map('escapeshellarg', $this->parts));

        echo $cmd . PHP_EOL;

        exec($cmd, $output, $code);

        echo implode(PHP_EOL, $output);

        return $code;
    }
}
