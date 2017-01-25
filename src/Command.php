<?php 

namespace Imbrish\LetsEncrypt;

class Command {
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
    public function __construct($cmd, $args)
    {
        $this->parts = [
            PHP_BINARY,
            $cmd,
        ];

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

        return shell_exec($cmd);
    }
}
