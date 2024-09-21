# Run one PHP script many times with different parameters and get only a certain number of messages about the results (do not wait for all running processes to execute).
```php
<?php

    use \JustMisha\MultiRunner\ScriptMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    try {
        $runner = ScriptMultiRunner($maxParallelProcesses, "/full/path/to/script");
    } catch (RuntimeException $e) {
        // handle an exception
    }

    for($i = 1; $i <= 1000000; $i++) {
        $changingArg1 = $i - 1;
        $changingArg2 = $i + 1;
        $runner->addProcess((string)$i, $changingArg, $changingArg2);
    }
    
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    $resultsNumberToAwait = 10;
    try {
        $results = $runner->runAndWaitForTheFirstNthResults($timeout, $resultsNumberToAwait);
    } catch (RuntimeException $e) {
        // handle an exception
    }
    
    foreach ($results as $processId => $processResult) {
        if $processResult->exitCode !== 0 {
            echo "There were errors in " . $processId . ": " . $processResult->stderr;
            continue;
        }
        $result = $processResult->stdout;
        // handle a success result, whatever it is
        
    };
```