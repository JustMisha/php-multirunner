<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\ScriptMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class ScriptMultiRunnerTest extends BaseTestCase
{

    public function testStandardRun(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
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

    public function testRunWhenDelay(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_hello.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
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

    public function testRunCmdOrBashWithoutDelay(): void
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
        for($i = 1; $i <= $totalProcessNums; $i++) {
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
     * @group python
     * @return void
     * @throws \Exception
     */
    public function testRunPythonWithoutDelay(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures'. DIRECTORY_SEPARATOR . 'print_hello.py';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath, null,
            'python', [], ['PATH' => getenv('Path'), 'SYSTEMROOT' => getenv('SYSTEMROOT')]);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
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

    public function testRunWithDelayAndComplicatedArgument(): void
    {
        $complicatedArgument = 'String with "C:\\quotes\\" or malicious %OS% (&()[]{}^=;!\'+,`~) stuff \\';
        $complicatedArgument .= '&()[]{}^=;!\'+,`~';
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_1st_argument.php';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, $complicatedArgument);
        }

        $timeout = 5;
        $programRunningTime = 3;
        $startTime = microtime(true);
        $results = $runner->runAndWaitForResults($timeout);
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan($totalProcessNums * $programRunningTime, $totalTime);

        $expectedResult = new ProcessResults(0,  $complicatedArgument,"");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    public function testRunWithCWD(): void
    {
        $scriptFullPath = 'echo_hello.php';
        $cwd = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'fixtures';
        $runner = new ScriptMultiRunner(self::MAX_PARALLEL_PROCESSES, $scriptFullPath, $cwd);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
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
}