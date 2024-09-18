# Run the PHP code many times with different parameters and get its output messages.
```php
<?php

    use \JustMisha\MultiRunner\CodeMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    $code = <<<CODE
<?php
// do something which takes a while
echo $resultOfDoing;
CODE;

    try {
        $runner = CodeMultiRunner($maxParallelProcesses, $code);
    } catch (Throwable $t) {
        // handle an exception
    }

    for($i = 1; $i <= 1000000; $i++) {
        $changingArg1 = $i - 1;
        $changingArg2 = $i + 1;
        $runner->addProcess((string)$i, $changingArg, $changingArg2);
    }
    
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    try {
        $results = $runner->runAndWaitForResults($timeout);
    } catch (RuntimeException $e) {
        // handle an exception
    }
    
    foreach($results as $processId => $processResult) {
        if $processResult['exitCode'] !== 0 {
            echo "There were errors in " . $processId . ": " . $processResult['stderr'];
            continue;
        }
        $result = $processResult['stdout'];
        // handle a success result, whatever it is
        
    };
```
