<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\DiffProgramMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class DiffProgramMultiRunnerTest extends BaseTestCase
{

    public function testStandardRun(): void
    {
        $runner = new DiffProgramMultiRunner(100);

        $totalProcessNums = 5;
        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess(
                (string)$i,
                dirname(__FILE__, 2)
                . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
                ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld')
            );
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

    public function testRealDiffProgramMultiRun(): void
    {
        $runner = new DiffProgramMultiRunner(100);

        $totalProcessNums = 2;

        $runner->addProcess(
            '1',
            dirname(__FILE__, 2)
            . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld')
        );
        $runner->addProcess(
            '2',
            dirname(__FILE__, 2) . DIRECTORY_SEPARATOR .
            'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'sleep_3_and_optput_helloworld_win64.exe' : 'sleep_3_and_output_helloworld')
        );


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