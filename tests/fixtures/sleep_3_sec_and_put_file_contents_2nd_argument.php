<?php

sleep(1);
$output = $argv[2] ?? '';
$outputFile = dirname(__FILE__,2) .
    DIRECTORY_SEPARATOR . 'tmp' .  DIRECTORY_SEPARATOR . 'complicatedArguments' .
    DIRECTORY_SEPARATOR . $argv[1];
var_dump($argv[2]);
var_dump($outputFile);
file_put_contents($outputFile, $output);
