<?php

/**
 * MultiRunner test classes: CodeMultiRunnerRunTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use Exception;
use JustMisha\MultiRunner\CodeMultiRunner;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\Tests\BaseTestCase;
use Throwable;

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
        parent::setUp();
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

    /**
     * @return void
     */
    public function testRunAndWaitForResultsWorksWhenMaxProcessLimitLessThenLaunchedProcesses(): void
    {
        $this->runAndWaitForResultsTest(60, 5, 10, '<?php echo "Hello!";');
    }

    /**
     * @return void
     * @throws Exception If we cannot read contents of $fileFullPathWithLongContents.
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testRunAndWaitForResultsWorksWhenEchoArgs(): void
    {

        $scriptText = <<<'NOWDOC'
            <?php
            echo "$argv[1]";
            NOWDOC;
        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner(5, $scriptText, 'php', [], $baseFolder, null, null);

        for ($i = 1; $i <= 10; $i++) {
            $runner->addProcess('string' . $i, (string)$i);
        }

        $results = $runner->runAndWaitForResults(5);

        $expectedResult = new ProcessResults(0, "1", "");
        $this->assertEquals($expectedResult, $results['string' . 1]);

        $expectedResult = new ProcessResults(0, "10", "");
        $this->assertEquals($expectedResult, $results['string' . 10]);
    }

    /**
     * @return void
     */
    public function testRunAndWaitForResultsThroughExceptionIfTimeout(): void
    {
        $this->expectExceptionMessage('Timeout: current time');
        $this->runAndWaitForResultsTest(1, 5, 1000, '<?php echo "Hello!";');
    }

    /**
     * @return void
     */
    public function testRunAndWaitForResultsWorksWhenCmdOrBashInterpreter(): void
    {
        $baseFolder = $this->runtimeFullPath;
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = 'Hello';
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
            $runner->addProcess('string' . $i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);

        $this->assertEquals($result, trim($results['string' . 1]->stdout));

        $this->assertEquals($result, trim($results['string' . $totalProcessNums]->stdout));

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @group python
     * @return void
     * @throws Throwable If interpreter python not found.
     */
    public function testRunAndWaitForResultsWorksWhenThePythonInterpreter(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = "Hello";
        $baseFolder = $this->runtimeFullPath;
        $interpreter = PYTHON_INTERPRETER_INVOCATION_NAME;
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
        } catch (Throwable $t) {
            if ($t->getMessage() === 'Interpreter python not found') {
                echo PHP_EOL;
                echo 'Interpreter python not found. Skip the test.' . PHP_EOL;
                $this->assertTrue(true);
            }
            throw $t;
        }

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, $result, "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results['string' . "1"]);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @return void
     * @throws Throwable If interpreter node not found.
     * @group node
     */
    public function testRunAndWaitForResultsWorksWhenTheNodeInterpreter(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 10;
        $result = "Hello";
        $baseFolder = $this->runtimeFullPath;
        $interpreter = NODE_INTERPRETER_INVOCATION_NAME;
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
        } catch (Throwable $t) {
            if ($t->getMessage() === 'Interpreter node not found') {
                echo PHP_EOL;
                echo 'Interpreter node not found. Skip the test.' . PHP_EOL;
                $this->assertTrue(true);
                return;
            }
            throw $t;
        }

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $expectedResult = new ProcessResults(0, $result, "");

        $this->assertCount($totalProcessNums, $results);
        $this->assertEquals($expectedResult, $results['string' . '1']);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);
        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @return void
     * @throws Exception If scandir for $tmpFolder return false.
     */
    public function testsRunAndForgetWorks(): void
    {
        $baseFolder = $this->runtimeFullPath;
        $tmpFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR
            . self::TMP_DIR_NAME . DIRECTORY_SEPARATOR . '_'  . __FUNCTION__ . time();
        if (!file_exists($tmpFolder)) {
            mkdir($tmpFolder, 0777, true);
        }
        $sleepTime = 3;
        $scriptText = '<?php' . PHP_EOL . 'sleep(' . $sleepTime . ');' . PHP_EOL .
            "file_put_contents('" . $tmpFolder . '\' . DIRECTORY_SEPARATOR . $argv[1], $argv[1]);' . PHP_EOL;

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

        $scandirResult = scandir($tmpFolder);
        if ($scandirResult === false) {
            throw new Exception("Scandir for $tmpFolder return false.");
        }

        $files = array_diff($scandirResult, array('..', '.'));
        $this->assertCount($maxProcessNums, $files);

        // Post-test cleaning.
        foreach ($files as $file) {
            unlink($tmpFolder . DIRECTORY_SEPARATOR . $file);
        }
        rmdir($tmpFolder);
        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @return void
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

    /**
     * @return void
     */
    public function testRunAndWaitForTheFirstNthResultsWorks(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 100;
        $result = 'Hello!';

        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner(
            $maxParallelProcessNums,
            '<?php' . PHP_EOL . 'echo "Hello!";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $resultsNumberToAwait = 10; // = $maxParallelProcesses which is a minimum chunk

        $results = $runner->runAndWaitForTheFirstNthResults($timeout, $resultsNumberToAwait);

        $expectedResult = new ProcessResults(0, $result, "");
        unset($runner);

        $this->assertTrue(count($results) >= $resultsNumberToAwait && count($results) < $totalProcessNums);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $resultsNumberToAwait]);

        $this->assertFolderEmpty($baseFolder);
    }

    /**
     * @return void
     */
    public function testRunAndWaitForTheFirstNthResultsWorksWithoutBaseFolder(): void
    {
        $timeout = 10;
        $maxParallelProcessNums = 10;
        $totalProcessNums = 100;
        $result = 'Hello!';

        $baseFolder = null;
        $runner = new CodeMultiRunner(
            $maxParallelProcessNums,
            '<?php' . PHP_EOL . 'echo "Hello!";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i);
        }

        $resultsNumberToAwait = 10; // = $maxParallelProcesses which is a minimum chunk

        $results = $runner->runAndWaitForTheFirstNthResults($timeout, $resultsNumberToAwait);

        $expectedResult = new ProcessResults(0, $result, "");

        unset($runner);

        $this->assertTrue(count($results) >= $resultsNumberToAwait && count($results) < $totalProcessNums);
        $this->assertEquals($expectedResult, $results['string' . 1]);
        $this->assertEquals($expectedResult, $results['string' . $resultsNumberToAwait]);
    }

    /**
     * The helper method for executing
     * the CodeMultiRunner->runAndWaitForResults method in tests.
     *
     * @param integer $timeout Seconds to wait for results.
     * @param integer $maxParallelProcessNums Maximum number of parallel processes.
     * @param integer $totalProcessNums Total number of running processes.
     * @param string $scriptText The text of the script to run.
     * @param ProcessResults|null $expectedResult An expected result object.
     * @return void
     */
    private function runAndWaitForResultsTest(
        int $timeout,
        int $maxParallelProcessNums,
        int $totalProcessNums = 10,
        string $scriptText = '<?php' . PHP_EOL . 'echo "Hello!";',
        ?ProcessResults $expectedResult = null
    ): void {
        if (is_null($expectedResult)) {
            $expectedResult = new ProcessResults(0, 'Hello!', '');
        }
        $baseFolder = $this->runtimeFullPath;
        $runner = new CodeMultiRunner($maxParallelProcessNums, $scriptText, 'php', [], $baseFolder, null, null);

        for ($i = 1; $i <= $totalProcessNums; $i++) {
            $runner->addProcess('string' . $i, (string)$i);
        }

        $results = $runner->runAndWaitForResults($timeout);

        $this->assertCount(($totalProcessNums), $results);
        $this->assertEquals($expectedResult, $results['string1']);
        $this->assertEquals($expectedResult, $results['string' . $totalProcessNums]);

        unset($runner);

        $this->assertFolderEmpty($baseFolder);
    }
}
