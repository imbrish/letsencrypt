<?php

namespace Imbrish\LetsEncrypt;

use Composer\Script\Event;

class Composer
{
    /**
     * Create config file based on example.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function createConfig(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        copy('config.yml.example', 'config.yml');
    }

    /**
     * Copy acme command.
     * It has to be put to /bin so that it is able to find vendor classes.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function copyAcme(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        copy('vendor/kelunik/acme-client/bin/acme', 'bin/acme');
    }
}
