<?php
set_time_limit(0);

$startTime = microtime(true);

require_once(dirname(__FILE__, 2) . '/src/MultiRunner.php');
require_once(dirname(__FILE__, 2) . '/src/CodeMultiRunner.php');
require_once(dirname(__FILE__, 2) . '/src/OsCommandsWrapper.php');

$runner = new \JustMisha\MultiRunner\CodeMultiRunner(
    200,
    'echo "Hahaha"; sleep(3);',
    'php',
    [],
    'runtime'
);

for($i = 1; $i < 1000; $i++) {
    $runner->addProcess((string)$i);
}

$executionTime = microtime(true) - $startTime;
echo ' took ' . round($executionTime, 5, PHP_ROUND_HALF_UP) . ' seconds to add all processes' . "\n";

$results = $runner->runAndWaitForResults(60);

$executionTime = microtime(true) - $startTime;
echo ' took ' . round($executionTime, 5, PHP_ROUND_HALF_UP) . ' seconds to run and await all results' . "\n";

//print_r($results);

$executionTime = microtime(true) - $startTime;
echo ' took ' . round($executionTime, 5, PHP_ROUND_HALF_UP) . ' seconds to process' . "\n";







