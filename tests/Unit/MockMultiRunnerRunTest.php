<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\CodeMultiRunner;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\MultiRunnerInterface;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class MockMultiRunnerRunTest extends BaseTestCase
{
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        $this->clearRuntimeFolder();
    }

    public function testWeCanUseMultiRunnerInterfaceForMocking(): void
    {
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $scriptText = '<?php' . PHP_EOL . 'echo "Hahaha";';
        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';
        $runner = new CodeMultiRunner($maxParallelProcessNums, $scriptText, 'php', [], $baseFolder, null, null);

        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }
        $expectedResult = new ProcessResults(0, 'Hahaha', '');
        $this->testRunner(60, 10, $expectedResult, $runner);
        unset($runner);
        $this->assertBaseFolderClear($baseFolder);

        $runner = new CodeMultiRunner($maxParallelProcessNums, $scriptText, 'php', [], $baseFolder, null, null);

        for($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }
        $expectedResult = new ProcessResults(0, 'Hahaha', '');
        $this->testRunner(60, 10, $expectedResult, $runner);
        unset($runner);
        $this->assertBaseFolderClear($baseFolder);

        // Create a mock MultiRunner to pass as an argument.
        $runner = new class() implements MultiRunnerInterface {
            public function runAndWaitForResults(int $waitTime): array
            {
                $array = [];
                for ($i = 1; $i < 11; $i++) {
                    $array[$i] = new ProcessResults(0, 'Hahaha', '');
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

        $expectedResult = new ProcessResults(0, 'Hahaha', '');
        $this->testRunner(60, 10, $expectedResult, $runner);
        unset($runner);
        $this->assertBaseFolderClear($baseFolder);
    }

    /**
     * @param int $timeout
     * @param int $totalProcessNums
     * @param ProcessResults $expectedResult
     * @throws \Exception
     */
    private function testRunner(
        int $timeout,
        int $totalProcessNums,
        ProcessResults $expectedResult = null,
        MultiRunnerInterface $runner
    ): void
    {
        if (is_null($expectedResult)) {
            $expectedResult = new ProcessResults(0, 'Hahaha', '');
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);
    }
}