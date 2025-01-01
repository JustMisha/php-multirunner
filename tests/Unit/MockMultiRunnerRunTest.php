<?php

/**
 * MultiRunner test classes: MockMultiRunnerRunTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\MultiRunnerInterface;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 *
 */
class MockMultiRunnerRunTest extends BaseTestCase
{
    /**
     * We can use the implementation of MultiRunnerInterface for testing.
     *
     * @return void
     */
    public function testWeCanUseMultiRunnerInterfaceForTesting(): void
    {
        $totalProcessNums = 10;
        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';

        $expectedResult = new ProcessResults(0, 'Hello!', '');

        $runner = $this->createMockRunnerImplementingMultiRunnerInterface($totalProcessNums, $expectedResult);

        $timeout = 1;
        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results["string1"]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * Creates a simple MultiRunnerInterface implementation for testing.
     *
     * @param integer $totalProcessNums The total number of processes.
     * @param ProcessResults $expectedResult The ProcessResults object that the class must return.
     * @return MultiRunnerInterface
     */
    protected function createMockRunnerImplementingMultiRunnerInterface(
        int $totalProcessNums,
        ProcessResults $expectedResult
    ): MultiRunnerInterface {
        return new class ($totalProcessNums, $expectedResult) implements MultiRunnerInterface
        {
            private ProcessResults $expectedResult;
            private int $totalProcessNums;

            public function __construct(int $totalProcessNums, ProcessResults $expectedResult)
            {
                $this->expectedResult = $expectedResult;
                $this->totalProcessNums = $totalProcessNums;
            }

            public function runAndWaitForResults(int $waitTime): array
            {
                $array = [];
                for ($i = 1; $i <= $this->totalProcessNums; $i++) {
                    $array["string". $i] = $this->expectedResult;
                }
                return $array;
            }

            public function runAndForget(int $waitTime): void
            {
                return;
            }

            public function runAndWaitForTheFirstNthResults(int $waitTime, int $resultsNumberToAwait): array
            {
                return [];
            }
        };
    }
}
