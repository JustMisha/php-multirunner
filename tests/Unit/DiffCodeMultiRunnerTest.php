<?php

/**
 * MultiRunner test classes: DiffCodeMultiRunnerTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use Exception;
use JustMisha\MultiRunner\DiffCodeMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;
use Throwable;

/**
 * Tests multiple running instances of different codes simultaneously.
 *
 */
class DiffCodeMultiRunnerTest extends BaseTestCase
{
    /**
     * Setup of global variables before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        parent::setUp();
    }

    /**
     * Tests that we can run python and cmd/bash script in parallel.
     *
     * @group python
     * @return void
     * @throws Exception If Interpreter python not found.
     */
    public function testRunPythonAndCmdOrBashCodeWorks(): void
    {
        $baseFolder = $this->runtimeFullPath;
        $timeout = 5;
        $maxParallelProcessNums = 10;

        $result = 'Hello world!';
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
        } catch (Throwable $t) {
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
}
