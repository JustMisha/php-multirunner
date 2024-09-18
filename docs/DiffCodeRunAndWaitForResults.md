# Run PHP, python, and node.js code with different parameters and get its output messages.
```php
<?php

    use \JustMisha\MultiRunner\DiffCodeMultiRunner;

    $maxParallelProcesses = 512;    //  determined by the machine on which it is runs

    try {
        $runner = new DiffCodeMultiRunner($maxParallelProcessNums);
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }

    $phpCode = <<<CODE
<?php
echo $argv[1];
CODE;    
    try {
        $runner->addProcess('phpCode', $phpCode, 'php', [], null, '123');
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }
    
    $pythonCode = <<<CODE
import sys
print(sys.argv[1], sep = None, end = '')
CODE;
    try {
        $runner->addProcess('pythonCode', $pythonCode, 'python3', [], getenv(), '456');
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }
    
    $nodeCode = <<<CODE
process.stdout.write(process.argv[2])
CODE;
    try {
        $runner->addProcess('nodeCode', $nodeCode, 'node', [], null, '789');
    } catch (RuntimeException $e) {
        // handle a runtime exception 
    }
    
    try {
        $results = $runner->runAndWaitForResults($timeout);
    } catch (RuntimeException $e) {
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