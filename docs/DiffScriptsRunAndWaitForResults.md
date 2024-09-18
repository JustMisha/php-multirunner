# Run different scripts with different parameters and get his messages about the results of the work
```php
<?php
    use \JustMisha\MultiRunner\ScriptMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs
    $runner = DiffScriptMultiRunner($maxParallelProcesses);
    
    $arg1 = 'something';
    $arg2 = 'anotherThing';

    try {
        $runner->addProcess('phpScript', "phpScriptFileName", "phpScriptDirFullPath", 'php', [], null, $arg1, $arg2);
    } catch (RuntimeException $e) {
        // handle a runtime exception
    }
    try {
        $runner->addProcess('pythonScript', "pythonScriptFileName", "pythonScriptDirFullPath", 'python', [], null, $arg1, $arg2);
    } catch (RuntimeException $e) {
        // handle a runtime exception
    }
    
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    try {
        $results = $runner->runAndWaitForResults($timeout);
    } catch (RuntimeException $t) {
        // handle a runtime exception
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