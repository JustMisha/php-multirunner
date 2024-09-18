<?php

namespace Tests\Unit;

use JustMisha\MultiRunner\DiffCodeMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class DiffCodeMultiRunnerTest extends BaseTestCase
{
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        $this->clearRuntimeFolder();
    }

    /**
     * @group python
     * @throws \Exception
     */
    public function testRunPythonAndCmdOrBash(): void
    {
        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';
        $timeout = 5;
        $maxParallelProcessNums = 10;

        $result = 'Hahaha';
        $interpreterArgs = [];

        if ($this->isWindows()) {
            $interpreter = 'cmd';
            $interpreterArgs = ['/c'];
            $scriptText = "@echo off" . PHP_EOL . " echo | set /p dummyName=" . $result;
        } else {
            $interpreter = 'bash';
            $scriptText = 'echo ' . $result;
        }

        $runner = new DiffCodeMultiRunner($maxParallelProcessNums, $baseFolder, null);

        $runner->addProcess('cmdOrBash', $scriptText, $interpreter, $interpreterArgs, null);

        $this->assertTrue(true);

        $interpreter = 'python';
        $scriptText = "print('" . $result . "', sep = None, end = '')";

        try {
            $runner->addProcess('python', $scriptText, $interpreter, [], null);
        } catch (\Throwable $t) {
            if ($t->getMessage() === 'Interpreter python not found') {
                echo PHP_EOL;
                echo 'Interpreter python not found. Skip the test.' . PHP_EOL;
                $this->assertTrue(true);
            }
            return;
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(2, $results);
        $this->assertEquals($result, trim($results['cmdOrBash']['stdout']));
        $this->assertEquals($result, trim($results['python']['stdout']));

        unset($runner);
        $this->assertBaseFolderClear($baseFolder);

    }

//    public function testRunCmdInterpreter(): void
//    {
//        $descriptors = array(
//            0  => array("pipe", "r"),
//            1 => array("pipe", "w"),
//            2 => array("pipe", "r"),
//        );
//
//        $command[] = 'cmd';
//        $command[] = 'D:\Devs\phpMultiRunner\tests\runtime\1718603676\cmdOrBash_script.cmd';
//
//        $pid = proc_open($command, $descriptors, $pipes);
//        if ($pid === false) {
//            throw new RuntimeException("An error occurred during execution " . implode(', ', $command));
//        }
//        // from https://www.php.net/manual/en/function.proc-open.php#81317
//        stream_set_blocking($pipes[2], false);
//        if ($err = stream_get_contents($pipes[2])) {
//            throw new RuntimeException('Process could not be started: ' . $err);
//        }
//        stream_set_blocking($pipes[1], false);
//        var_dump(stream_get_contents($pipes[1]));
//        fclose($pipes[0]);
//        fclose($pipes[1]);
//        fclose($pipes[2]);
//
//        proc_close($pid);
//        $this->assertTrue(true);
//    }
//    /**
//     * @throws \Exception
//     */
//    public function testRunPythonInterpreter(): void
//    {
//        $timeout = 10;
//        $maxParallelProcessNums = 10;
//        $usePipe = true;
//        $totalProcessNums = 10;
//        $result= "Hahaha";
//        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';
//        $interpreter = 'python';
//        $interpreterArgs = '';
//        $scriptText = "print('" . $result . "', sep = None, end = '')";
//
//        try {
//            $runner = new ScriptMultiRunner($baseFolder, $scriptText, true, $maxParallelProcessNums, $usePipe, $interpreter, $interpreterArgs);
//        } catch (\Throwable $t) {
//            if ($t->getMessage() === 'Interpreter python not found') {
//                echo PHP_EOL;
//                echo 'Interpreter python not found. Skip the test.' . PHP_EOL;
//                $this->assertTrue(true);
//            }
//            return;
//        }
//
//        for($i = 1; $i <= $totalProcessNums; $i++) {
//            $runner->addInstance((string)$i);
//        }
//
//        $results = $runner->runAndWaitForResults($timeout);
//
//        $this->assertCount($totalProcessNums, $results);
//        $this->assertEquals($result, $results[1]);
//        $this->assertEquals($result, $results[($totalProcessNums)]);
//
//        unset($runner);
//        $this->assertBaseFolderClear($baseFolder);
//
//    }

    /**
     * Check whether a base folder clear
     * after destroying BackgroundParallelProcesses
     *
     * @param string $baseFolder
     * @return void
     */
    protected function assertBaseFolderClear(string $baseFolder): void
    {
        $dirIterator = new \FilesystemIterator($baseFolder, \FilesystemIterator::SKIP_DOTS);
        $this->assertFalse($dirIterator->valid());
    }

}