<?php

/**
 * MultiRunner test classes: ProgramMultiRunnerTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\ProgramMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * Testing multiple instances of a program running simultaneously.
 */
class ProgramMultiRunnerTest extends BaseTestCase
{
    /**
     * Tests we can run multiple instances of a program simultaneously.
     *
     * @return void
     */
    public function testWeCanRunMultipleInstancesOfAProgramSimultaneously(): void
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

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello world!", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests, we can run multiple instances of a long-running program simultaneously.
     *
     * @return void
     */
    public function testWeCanRunMultipleInstancesOfALongRunningProgramSimultaneously(): void
    {
        $runner = new ProgramMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            dirname(__FILE__, 2) . DIRECTORY_SEPARATOR .
            'fixtures' . DIRECTORY_SEPARATOR .
            ($this->isWindows() ? 'sleep_3_and_optput_helloworld_win64.exe' : 'sleep_3_and_output_helloworld'),
            [],
            null,
            null
        );

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $timeout = 5;
        $programRunningTime = 3;
        $startTime = microtime(true);
        $results = $runner->runAndWaitForResults($timeout);
        $totalTime = microtime(true) - $startTime;

        $this->assertLessThan($totalProcessNums * $programRunningTime, $totalTime);

        $expectedResult = new ProcessResults(0, "Hello world!", "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests, we can run multiple instances of a program simultaneously
     * with complicated arguments.
     *
     * @return void
     */
    public function testWeCanRunMultipleInstancesOfAProgramWithComplicatedArgumentsSimultaneously(): void
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
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i, $badArgument);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello world!", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
    }

    /**
     * Tests, we can run multiple instances of a program simultaneously
     * when the cwd is set up.
     *
     * @return void
     */
    public function testWeCanRunMultipleInstancesOfAProgramSimultaneouslyWhenCwdIsSetUp(): void
    {
        $runner = new ProgramMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            ($this->isWindows() ? 'helloworld_win64.exe' : 'helloworld'),
            [],
            dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR,
            null
        );

        $totalProcessNums = 5;
        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $timeout = 5;

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, "Hello world!", "");

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
    }
}
