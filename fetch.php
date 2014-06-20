<?php

/**
 *
 * Usage (in command line):
 *
 * php ./fetch.php
 *
 */

require_once __DIR__ . '/vendor/autoload.php';

use GeneralPattern\Config;
use GeneralPattern\Exceptions\InvalidInputException;
use GeneralPattern\LogSniffer;
use GeneralPattern\Log;

if (php_sapi_name() == 'cli') {
    $config = new Config(
        isset($argv[1])
            ? $argv[1]
            : './config.json'
    );

    try {
        LogSniffer::create($config)->run()->getResult();
    } catch (InvalidInputException $e) {
        die('Invalid configuration file.');
    }

} else {
    die("This script should be run from the command line.\n");
}
