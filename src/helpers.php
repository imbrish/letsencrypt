<?php

use Imbrish\LetsEncrypt\Command;

// send email notification

function notify($subject, $message) {
    global $climate, $config;

    if (! $climate->arguments->defined('notify')) {
        return;
    }

    $address = $climate->arguments->get('notify') ?: $config['notify'];

    mail($address, $subject, $message);
}

// report processing error

function error($message) {
    global $climate;

    notify($message, Command::$last . PHP_EOL . Command::$output);

    $climate->error($message);
    exit(EX_PROCESSING_ERROR);
}

// recursively remove directory

function rrmdir($dir) {
    if (! is_dir($dir)) {
        return;
    }

    foreach (array_diff(scandir($dir), ['.','..']) as $file) {
        if (is_dir($path = $dir . '/' . $file)) {
            rrmdir($path);
        }
        else {
            unlink($path);
        }
    }

    rmdir($dir);
}
