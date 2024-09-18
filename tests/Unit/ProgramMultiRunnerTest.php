<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\ProgramMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class ProgramMultiRunnerTest extends BaseTestCase
{

    public function testStandardRun(): void
    {
        $runner = new ProgramMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            dirname(__FILE__, 2)
            . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld'),
            [],
            null,
            null);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = [
            'exitCode' => 0,
            'stdout' => "Hello world!",
            'stderr' => "",
        ];

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);

    }

    public function testRunWhenDelay(): void
    {
        $runner = new ProgramMultiRunner(self::MAX_PARALLEL_PROCESSES,
            dirname(__FILE__, 2) . DIRECTORY_SEPARATOR .
            'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'sleep_3_and_optput_helloworld_win64.exe' : 'sleep_3_and_output_helloworld'),
            [],
            null,
            null);

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

        $expectedResult = [
            'exitCode' => 0,
            'stdout' => "Hello world!",
            'stderr' => "",
        ];

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);

    }

    //

    public function testRunWithBadArgument(): void
    {
        $runner = new ProgramMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            dirname(__FILE__, 2)
            . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld'),
            [],
            null,
            null
        );

        $badArgument = 'String with "C:\\quotes\\" or malicious %OS% (&()[]{}^=;!\'+,`~) stuff \\';
        $badArgument .= '&()[]{}^=;!\'+,`~';
        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, $badArgument);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = [
            'exitCode' => 0,
            'stdout' => "Hello world!",
            'stderr' => "",
        ];

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);

    }

    public function testRunWithCWD(): void
    {
        $runner = new ProgramMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld'),
            [], dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR,
            null);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = [
            'exitCode' => 0,
            'stdout' => "Hello world!",
            'stderr' => "",
        ];

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);

    }
}