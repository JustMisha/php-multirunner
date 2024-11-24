<?php

/**
 * MultiRunner final class: CodeMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use Exception;
use JustMisha\MultiRunner\CodeMultiRunner;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * Tests multiple running instances of a code simultaneously.
 *
 */
class CodeMultiRunnerRunTest extends BaseTestCase
{
    /**
     * Setup each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        $this->clearRuntimeFolder();
    }

    /**
     *
     * @return void
     * @throws Exception If timeout exceeded.
     */
    public function testRunAndWaitForResultsWorksWhenMaxProcessLimitMoreThenLaunchedProcesses(): void
    {
        $this->runAndWaitForResultsTest(60, 20, 10);
    }

    public function testRunAndWaitForResultsWorksWhenMaxProcessLimitLessThenLaunchedProcesses(): void
    {
        $this->runAndWaitForResultsTest(60, 5, 10, '<?php echo "Hahaha";');
    }

    public function testRunAndWaitForResultsWorksWithLongEcho(): void
    {
        $fileFullPathWithLongContents = dirname(__FILE__, 2) . '/fixtures/longecho.txt';
        $result = file_get_contents($fileFullPathWithLongContents);
        if ($result === false) {
            throw new Exception("Cannot read contents of $fileFullPathWithLongContents.");
        }
        $expectedResult = new ProcessResults(0, $result, "");
        $this->runAndWaitForResultsTest(
            60,
            5,
            5,
            '<?php' . PHP_EOL . 'echo "' . $result . '";',
            $expectedResult
        );
    }

    public function testRunAndWaitForResultsWorksWithVeryLongEcho(): void
    {
        $result = str_repeat("a", 1000000);

        $expectedResult = new ProcessResults(0, $result, "");
        $this->runAndWaitForResultsTest(
            20,
            5,
            5,
            '<?php' . PHP_EOL . 'echo "' . $result . '";',
            $expectedResult
        );
    }

    public function testRunAndWaitForResultsWorksWhenVeryLongEchoWithPause(): void
    {
        $symbolsNumbers = 1000000;
        $scriptText = <<<HEREDOC
<?php
echo str_repeat("a", $symbolsNumbers);
sleep(3);
echo str_repeat("b", $symbolsNumbers);
HEREDOC;
        $result = str_repeat("a", $symbolsNumbers) . str_repeat("b", $symbolsNumbers);

        $expectedResult = new ProcessResults(0, $result, "");
        $this->runAndWaitForResultsTest(30, 5, 5, $scriptText, $expectedResult);
    }

    public function testRunAndWaitForResultsWorksWhenEchoArgs(): void
    {

        $scriptText = <<<'NOWDOC'
            <?php
            echo "$argv[1]";
            NOWDOC;
        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner(5, $scriptText, 'php', [], $baseFolder, null, null);

        for($i = 1; $i <= 10; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }

        $results = $runner->runAndWaitForResults(5);


        $expectedResult = new ProcessResults(0, "1", "");
        $this->assertEquals($expectedResult, $results[1]);

        $expectedResult = new ProcessResults(0, "10", "");
        $this->assertEquals($expectedResult, $results[10]);

    }

    public function testRunAndWaitForResultsThroughExceptionIfTimeout(): void
    {
        $this->expectExceptionMessage('Timeout: current time');
        $this->runAndWaitForResultsTest(1, 5, 1000, '<?php echo "Hahaha";');
    }

    /**
     * @throws Exception
     */
    public function testRunAndWaitForResultsWorksWhenCmdOrBashInterpreter(): void
    {
        $baseFolder = $this->runtimeFullPath;
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = 'Hahaha';
        if ($this->isWindows()) {
            $interpreter = 'cmd';
            $interpreterArgs = ['/c'];
            $echoOff = "@echo off" . PHP_EOL;
        } else {
            $interpreter = 'sh';
            $interpreterArgs = [];
            $echoOff = '';
        }
        $scriptText = $echoOff .  'echo ' . $result;

        $runner = new CodeMultiRunner(
            $maxParallelProcessNums,
            $scriptText,
            $interpreter,
            $interpreterArgs,
            $baseFolder,
            null,
            null
        );

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);

        $this->assertEquals($result, trim($results[1]->stdout));

        $this->assertEquals($result, trim($results[$totalProcessNums]->stdout));

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @group python
     * @throws Exception
     */
    public function testRunAndWaitForResultsWorksWhenThePythonInterpreter(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = "Hahaha";
        $baseFolder = $this->runtimeFullPath;
        $interpreter = 'python';
        $interpreterArgs = [];
        $scriptText = "print('" . $result . "', sep = None, end = '')";
        $envVars = getenv();

        try {
            $runner = new CodeMultiRunner(
                $maxParallelProcessNums,
                $scriptText,
                $interpreter,
                $interpreterArgs,
                $baseFolder,
                $envVars,
                null
            );
        } catch (\Throwable $t) {
            if ($t->getMessage() === 'Interpreter python not found') {
                echo PHP_EOL;
                echo 'Interpreter python not found. Skip the test.' . PHP_EOL;
                $this->assertTrue(true);
            }
            throw $t;
        }

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, $result, "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results["1"]);
        $this->assertEquals($expectedResult, $results[(string)$totalProcessNums]);

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @group node
     * @return void
     * @throws \ErrorException
     */
    public function testRunAndWaitForResultsWorksWhenTheNodeInterpreter(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = "Hahaha";
        $baseFolder = $this->runtimeFullPath;
        $interpreter = 'node';
        $interpreterArgs = [];
        $scriptText = "process.stdout.write('" . $result . "')";
        $envVars = null;
        try {
            $runner = new CodeMultiRunner(
                $maxParallelProcessNums,
                $scriptText,
                $interpreter,
                $interpreterArgs,
                $baseFolder,
                $envVars,
                null
            );
        } catch (\Throwable $t) {
            if ($t->getMessage() === 'Interpreter node not found') {
                echo PHP_EOL;
                echo 'Interpreter node not found. Skip the test.' . PHP_EOL;
                $this->assertTrue(true);
            }
            throw $t;
        }

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, $result, "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results['1']);
        $this->assertEquals($expectedResult, $results[(string)$totalProcessNums]);

        unset($runner);
        $this->assertFolderEmpty($baseFolder);

    }

    /**
     * @throws Exception
     */
    public function testsRunAndForgetWorks(): void
    {
        $baseFolder = $this->runtimeFullPath;
        $testFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR
            . 'proba' . DIRECTORY_SEPARATOR . '_'  . __FUNCTION__ . time();
        if (!file_exists($testFolder)) {
            mkdir($testFolder, 0777, true);
        }
        $sleepTime = 3;
        $scriptText = '<?php' . PHP_EOL . 'sleep(' . $sleepTime . ');' . PHP_EOL .
            "file_put_contents('" . $testFolder . '\' . DIRECTORY_SEPARATOR . $argv[1], $argv[1]);' . PHP_EOL;

        $runner = new CodeMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            $scriptText,
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        $startTime = microtime(true);
        $maxProcessNums = 17;
        for ($i = 1; $i <= $maxProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }

        $runner->runAndForget(15);

        $timeToRun = microtime(true) - $startTime;
        $timeReserve = 10;
        $maxExpectedTime = ceil($maxProcessNums / self::MAX_PARALLEL_PROCESSES) * $sleepTime + $timeReserve;
        $this->assertLessThan($maxExpectedTime, $timeToRun);

        sleep($sleepTime + $timeReserve);

        $scandirResult = scandir($testFolder);
        if ($scandirResult === false) {
            throw new Exception("Scandir for $testFolder return false.");
        }

        $files = array_diff($scandirResult, array('..', '.'));
        $this->assertCount($maxProcessNums, $files);

        // Post-test cleaning.
        foreach ($files as $file) {
            unlink($testFolder . DIRECTORY_SEPARATOR . $file);
        }
        rmdir($testFolder);
        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @throws Exception
     */
    public function testsRunAndForgetWorksWhenProcessOutputLongEcho(): void
    {
        $baseFolder = $this->runtimeFullPath;

        $sleepTime = 3;
        $scriptText = '<?php' . PHP_EOL . 'sleep(' . $sleepTime . ');' . PHP_EOL .
            "echo str_repeat('a', 10000000);" . PHP_EOL;

        $runner = new CodeMultiRunner(
            self::MAX_PARALLEL_PROCESSES,
            $scriptText,
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        $maxProcessNums = 5;
        for ($i = 1; $i <= $maxProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }

        $runner->runAndForget(60);

        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }

    public function testRunAndWaitForTheFirstNthResultsWorks(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 100;
        $result = 'Hahaha';

        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner(
            $maxParallelProcessNums,
            '<?php' . PHP_EOL . 'echo "Hahaha";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $resultsNumberToAwait = 10; // = $maxParallelProcesses which is a minimum chunk

        $results = $runner->runAndWaitForTheFirstNthResults($timeout, $resultsNumberToAwait);

        $expectedResult = new ProcessResults(0, $result, "");
        unset($runner);

        $this->assertTrue(count($results) >= $resultsNumberToAwait && count($results) < $totalProcessNums);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$resultsNumberToAwait]);

        $this->assertFolderEmpty($baseFolder);
    }

    public function testRunAndWaitForTheFirstNthResultsWorksWithoutBaseFolder(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 100;
        $result = 'Hahaha';

        $baseFolder = null;
        $runner = new CodeMultiRunner(
            $maxParallelProcessNums,
            '<?php' . PHP_EOL . 'echo "Hahaha";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i);
        }

        $resultsNumberToAwait = 10; // = $maxParallelProcesses which is a minimum chunk

        $results = $runner->runAndWaitForTheFirstNthResults($timeout, $resultsNumberToAwait);

        $expectedResult = new ProcessResults(0, $result, "");

        unset($runner);

        $this->assertTrue(count($results) >= $resultsNumberToAwait && count($results) < $totalProcessNums);
        $this->assertEquals($expectedResult, $results[1]);
        $this->assertEquals($expectedResult, $results[$resultsNumberToAwait]);
    }

    /**
     * @param int $timeout
     * @param int $maxParallelProcessNums
     * @param int $totalProcessNums
     * @param string $scriptText
     * @param ProcessResults|null $expectedResult
     * @return void
     */
    private function runAndWaitForResultsTest(
        int $timeout,
        int $maxParallelProcessNums,
        int $totalProcessNums = 10,
        string $scriptText = '<?php' . PHP_EOL . 'echo "Hahaha";',
        ProcessResults $expectedResult = null
    ): void
    {
        if (is_null($expectedResult)) {
            $expectedResult = new ProcessResults(0, 'Hahaha', '');
        }
        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner($maxParallelProcessNums, $scriptText, 'php', [], $baseFolder, null, null);

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess((string)$i, (string)$i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results["1"]);
        $this->assertEquals($expectedResult, $results[$totalProcessNums]);

        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }
}