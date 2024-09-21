# Run one program many times with different parameters and get its messages about the results of its work
```php
 <?php
    use \JustMisha\MultiRunner\ProgramMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    try {
        $runner = ProgramMultiRunner($maxParallelProcesses, "/full/path/to/program");
    } catch (RuntimeException $e) {
        // handle a runtime exception    
    }
    
    for($i = 1; $i <= 1000000; $i++) {
        $changingArg1 = $i - 1;
        $changingArg2 = $i + 1;
        $processId = (string)$i;
        $runner->addProcess($processId, $changingArg, $changingArg2);
    }
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    try {
        $results = $runner->runAndWaitForResults($timeout);
    } catch (RuntimeException $t) {
        // handle a runtime exception
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