<?php

namespace JustMisha\MultiRunner\Tests\Unit;

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
        parent::setUp();
    }

    /**
     * @group python
     * @throws \Exception
     */
    public function testRunPythonAndCmdOrBashCodeWorks(): void
    {
        $baseFolder = $this->runtimeFullPath;
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
        $this->assertEquals($result, trim($results['cmdOrBash']->stdout));
        $this->assertEquals($result, trim($results['python']->stdout));

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }


    /**
     * Check whether a base folder clear
     * after destroying BackgroundParallelProcesses
     *
     * @param string $dir
     * @return void
     */
    protected function assertFolderEmpty(string $dir): void
    {
        $dirIterator = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $this->assertFalse($dirIterator->valid());
    }
}