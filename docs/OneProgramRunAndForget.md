# Run one program many times with different parameters and do nothing else (run and forget)
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
        $runner->addProcess((string)$i, $changingArg, $changingArg2);
    }
    $timeout = 15; // Timeout in seconds, depending on the machine it is running on.
    try {
        $runner->runAndWaitForget($timeout);
    } catch (RuntimeException $t) {
        // handle a runtime exception
    }
```