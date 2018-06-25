<?php

use Imbrish\LetsEncrypt\Command;

// send email notification

function sendNotification($subject, $message) {
    global $climate, $config;

    if (! $climate->arguments->defined('notify')) {
        return;
    }

    $address = $climate->arguments->get('notify') ?: $config['notify'];

    $result = mail($address, $subject, $message);

    if (! $result) {
        $climate->to('error')->shout('Failed to send the email notification.');
    }
}

// report processing error and exit

function reportErrorAndExit($message) {
    global $climate;

    $climate->to('error')->error($message);

    sendNotification($message, Command::$last . PHP_EOL . Command::$output);

    exit(EX_PROCESSING_ERROR);
}

// recursively remove directory

function removeDirectory($dir) {
    if (! is_dir($dir)) {
        return;
    }

    foreach (array_diff(scandir($dir), ['.','..']) as $file) {
        if (is_dir($path = $dir . '/' . $file)) {
            removeDirectory($path);
        }
        else {
            unlink($path);
        }
    }

    rmdir($dir);
}

// convert smart quotes to regular quotes

function convertQuotes($str) {
    static $chars = [
       "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
       "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
       "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
       "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
       "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
       "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
       "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
       "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
       "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
       "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
       "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
       "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
    ];

    return strtr($str, $chars);
}
