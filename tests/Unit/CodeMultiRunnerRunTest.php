<?php

/**
 * MultiRunner test classes: CodeMultiRunnerRunTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use Exception;
use http\Exception\RuntimeException;
use InvalidArgumentException;
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
     * Tests that we can work when the child process output is real big
     * (for 3 processes up to 1/6th of the memory limit).
     *
     * The test can take a long time, so the timeout is set to 120 seconds.
     *
     * @return void
     */
    public function testRunAndWaitForResultsWorksWhenVeryLongEchoWithPause(): void
    {
        $totalProcessNums = 3;
        $memoryLimit = $this->iniStringToBytes(ini_get('memory_limit'));
        $halfSymbolsNumbersInOutput = (int) $memoryLimit / ($totalProcessNums * 4);
        $scriptText = <<<HEREDOC
<?php
echo str_repeat("a", $halfSymbolsNumbersInOutput);
sleep(3);
echo str_repeat("b", $halfSymbolsNumbersInOutput);
HEREDOC;
        $result = str_repeat("a", $halfSymbolsNumbersInOutput) . str_repeat("b", $halfSymbolsNumbersInOutput);
        $expectedResult = new ProcessResults(0, $result, "");
        $this->runAndWaitForResultsTest(120, 5, $totalProcessNums, $scriptText, $expectedResult);
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
            "echo str_repeat('a', 10_000_000);" . PHP_EOL;

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

    /**
     * Converts when a right $iniString is passed.
     *
     * @return void
     */
    public function testIniStringToBytesWorksWhenRightIniStringPassed(): void
    {
        $this->assertEquals(1024 * 1024 * 1024, $this->iniStringToBytes('1G'));
        $this->assertEquals(1024 * 1024, $this->iniStringToBytes('1M'));
        $this->assertEquals(1024, $this->iniStringToBytes('1K'));
    }

    public function wrongIniStrings(): array
    {
        return [
            'without digits' => ['Hello'],
            'wrong quantifier' => ['1234R'],
            'quantifier before digits with whitespace' => ['G 1245'],
            'quantifier before digits' => ['G1245'],
            'whitespace after digits' => ['128 M'],
        ];
    }

    /**
     *
     * @dataProvider wrongIniStrings
     * @return void
     */
    public function testIniStringToBytesThrowsExceptionWhenWrongIniStringPassed($wrongIniString): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->iniStringToBytes($wrongIniString);
    }

    /**
     * Convert a string like "128M" or "1G" or "14K"
     * returned the ini_get function to integer in bytes.
     *
     * @param string $iniString A string like "128M" or "1G" or "14K".
     * @return integer Bytes converted from $iniString.
     * @throws InvalidArgumentException If a $iniString isn't like "128M" or "1G" or "14K".
     */
    private function iniStringToBytes(string $iniString): int
    {
        if (preg_match('/([0-9]+[KMG])/i', $iniString) !== 1) {
            throw new InvalidArgumentException("A string must be like '128M' or '1G' or '14K'");
        }
        $iniString = trim($iniString);
        $num = (int) rtrim($iniString, 'KMG');
        $last = strtolower($iniString[strlen($iniString) - 1]);

        switch ($last) {
            case 'g':
                $num = $num * 1024 * 1024 * 1024;
                break;
            case 'm':
                $num = $num * 1024 * 1024;
                break;
            case 'k':
                $num *= 1024;
                break;
        }

        return $num;
    }
}
