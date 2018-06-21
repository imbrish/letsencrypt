<?php

namespace Imbrish\LetsEncrypt;

use Composer\Script\Event;

class Composer
{
    /**
     * Post install/post update callback.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function handle(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir') . '/autoload.php';

        // we need to put kelunik/acme-client executable to "bin" so that it is able to find composer's "autoload.php"
        copy('vendor/kelunik/acme-client/bin/acme', 'bin/acme');

        // we will calculate hash of the "composer.json" so that we can easily check whether it was changed
        file_put_contents('storage/composer.hash', md5(file_get_contents('composer.json')));
    }
}
