# Run one Python script many times with different parameters and get its output messages
```php
<?php
    use \JustMisha\MultiRunner\ScriptMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    try {
        $runner = ScriptMultiRunner($maxParallelProcesses, "/script_name", '/full/path/to/script/directory', 'python');
        // or if there is no need to change cwd
        // $runner = ScriptMultiRunner($maxParallelProcesses, "/full/path/to/script", null, 'python');
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }

    for($i = 1; $i <= 1000000; $i++) {
        $changingArg1 = $i - 1;
        $changingArg2 = $i + 1;
        $runner->addProcess((string)$i, $changingArg, $changingArg2);
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