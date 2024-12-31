<?php

/**
 * MultiRunner test classes: DiffProgramMultiRunnerTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\DiffProgramMultiRunner;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * A class that tests for the simultaneous execution
 * of multiple instances of different programs.
 */
class DiffProgramMultiRunnerTest extends BaseTestCase
{
    /**
     * Tests that we can run multiple instances of different programs simultaneously.
     *
     * @return void
     */
    public function testRealDiffProgramMultiRun(): void
    {
        $runner = new DiffProgramMultiRunner(100);

        $totalProcessNums = 2;

        $runner->addProcess(
            'string1',
            dirname(__FILE__, 2)
            . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld')
        );
        $runner->addProcess(
            'string2',
            dirname(__FILE__, 2) . DIRECTORY_SEPARATOR .
            'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'sleep_3_and_optput_helloworld_win64.exe' : 'sleep_3_and_output_helloworld')
        );

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello world!", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results['string1']);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
    }
}
