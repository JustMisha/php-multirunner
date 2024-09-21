<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\DiffScriptMultiRunner;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class DiffScriptMultiRunnerTest extends BaseTestCase
{

    public function testStandardRun(): void
    {

        $runner = new DiffScriptMultiRunner(self::MAX_PARALLEL_PROCESSES);

        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.php';
        $runner->addProcess('1', $scriptFullPath);

        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_hello.php';
        $runner->addProcess('2', $scriptFullPath);

        if ($this->isWindows()) {
            $scriptFullPath = dirname(__FILE__, 2) .
                DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.cmd';
            $runner->addProcess('3', $scriptFullPath, null, 'cmd', ['/c']);
        } else {
            $scriptFullPath = dirname(__FILE__, 2) .
                DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_hello.bash';
            $runner->addProcess('3', $scriptFullPath, null, 'bash');
        }

        $scriptFullPath = 'echo_hello.php';
        $cwd = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'fixtures';
        $runner->addProcess('4', $scriptFullPath, $cwd);

        $complicatedArgument = 'String with "C:\\quotes\\" or malicious %OS% (&()[]{}^=;!\'+,`~) stuff \\';
        $complicatedArgument .= '&()[]{}^=;!\'+,`~';
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'sleep_3_sec_and_echo_1st_argument.php';
        $runner->addProcess('5', $scriptFullPath, null, 'php', [], null, $complicatedArgument);

        $totalProcessNums = 5;

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);



        $this->assertCount(($totalProcessNums), $results);

        $expectedResult = new ProcessResults(0, "Hello", "");
        $this->assertEquals($expectedResult, $results[1]);

        $expectedResult = new ProcessResults(0, $complicatedArgument, "");
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);
    }

    public function testRunWithEnvVarsSet(): void
    {
        $scriptFullPath = dirname(__FILE__, 2) .
            DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'echo_env_hello.php';
        $runner = new DiffScriptMultiRunner(self::MAX_PARALLEL_PROCESSES);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, $scriptFullPath, null, 'php', [], ['Hello' => 'Hello'], (string)$i);
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