<?php

/**
 * class ScriptMultiRunnerTest
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\ScriptMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * Tests ScriptMultiRunner class.
 *
 */
class ScriptMultiRunnerTest extends BaseTestCase
{
    /**
     * Tests that we can run multiple processes
     * of the same script simultaneously,
     * when the script is running without delay.
     *
     * @return void
     */
    public function testRunMultipleProcessesOfScriptWhenScriptRunsWithoutDelay(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests that we can run multiple processes
     * of the same script simultaneously,
     * when the script is running with delay.
     *
     * @return void
     */
    public function testRunMultipleProcessesOfScriptWhenScriptRunsWithDelay(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_hello.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;
        $programRunningTime = 3;
        $startTime = microtime(true);
        $results = $runner->runAndWaitForResults($timeout);
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan($totalProcessNums * $programRunningTime, $totalTime);

        $expectedResult = new ProcessResults(0, "Hello", "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests that we can run multiple processes
     * of the same cmd or bash script simultaneously,
     * when the script is running without delay.
     *
     * @return void
     */
    public function testRunMultipleProcessesOfCmdOrBashWhenScriptRunsWithoutDelay(): void
    {
        if ($this->isWindows()) {
            $scriptFullPath = dirname(__FILE__, 2) .
                DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.cmd';
            $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath, null, 'cmd', ['/c']);
        } else {
            $scriptFullPath = dirname(__FILE__, 2) .
                DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.bash';
            $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath, null, 'bash');
        }


        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests that we can run multiple processes
     * of the same python script simultaneously,
     * when the script is running without delay.
     *
     * @group python
     * @return void
     */
    public function testRunMultipleProcessesOfPythonScriptWhenScriptRunsWithoutDelay(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'print_hello.py';
        $runner = new ScriptMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            $scriptFullPath,
            null,
            'python',
            [],
            ['PATH' => getenv('Path'), 'SYSTEMROOT' => getenv('SYSTEMROOT')]
        );

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests that we can run multiple processes
     * of the same script simultaneously,
     * when the script is running with delay
     * and with complicated arguments.
     *
     * @group python
     * @return void
     */
    public function testRunMultipleProcessesOfScriptWhenScriptRunsWithDelayAndComplicatedArgument(): void
    {
        $complicatedArgument = 'String with "C:\\quotes\\" or malicious %OS% (&()[]{}^=;!\'+,`~) stuff \\';
        $complicatedArgument .= '&()[]{}^=;!\'+,`~';
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_1st_argument.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, $complicatedArgument);
        }

        $timeout = 5;
        $programRunningTime = 3;
        $startTime = microtime(true);
        $results = $runner->runAndWaitForResults($timeout);
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan($totalProcessNums * $programRunningTime, $totalTime);

        $expectedResult = new ProcessResults(0, $complicatedArgument, "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    public function testRunAndJustForgetWithDelayAndComplicatedArgument(): void
    {
        $complicatedArgument = 'String with "C:\\quotes\\" or malicious %OS% (&()[]{}^=;!\'+,`~) stuff \\';
        $complicatedArgument .= '&()[]{}^=;!\'+,`~';
        $tmpDir = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR .
            self::TMP_DIR_NAME .  DIRECTORY_SEPARATOR . 'complicatedArguments';
        if (!file_exists($tmpDir)) {
            $oldMask = umask();
            mkdir($tmpDir, 0777, true);
            umask($oldMask);
        }

        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
            'sleep_3_sec_and_put_file_contents_2nd_argument.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i, $complicatedArgument);
        }

        $timeout = 5;
        $programRunningTime = 3;
        $startTime = microtime(true);
        $runner->runAndForget($timeout);
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan($totalProcessNums * $programRunningTime, $totalTime);

        sleep($programRunningTime * 2);

        $fileContents1 = file_get_contents($tmpDir . DIRECTORY_SEPARATOR . '1');
        $fileContents5 = file_get_contents($tmpDir . DIRECTORY_SEPARATOR . '5');

        $this->osCommandsWrapper->removeDirRecursive($tmpDir);


        $this->assertEquals($complicatedArgument, $fileContents1);
        $this->assertEquals($complicatedArgument, $fileContents5);

        unset($runner);
    }

    public function testRunWithCWD(): void
    {
        $scriptFullPath = 'echo_hello.php';
        $cwd = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'fixtures';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath, $cwd);

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }


    public function testWeCanRunScriptUsingEnvVarsSet(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_env_hello.php';
        $runner = new ScriptMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            $scriptFullPath,
            null,
            'php',
            [],
            ['Hello' => 'Hello']
        );

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);

        $expectedResult = new ProcessResults(0, "Hello1", "");
        $this->assertEquals($expectedResult, $results[1]);

        $expectedResult = new ProcessResults(0, "Hello"  . $totalProcessNums, "");
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }
}
