<?php

/**
 * MultiRunner main class: MultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use ErrorException;
use RuntimeException;
use Throwable;
use JustMisha\MultiRunner\DTO\ProcessInQueueData;
use JustMisha\MultiRunner\DTO\ProcessResults;
use JustMisha\MultiRunner\DTO\RunningProcessData;
use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

/**
 * This abstract class contains the main logic for
 * running multiple processes in parallel and
 * possibly getting their results (outputs).
 *
 * The public methods for this are:
 * - {@see MultiRunner::runAndWaitForResults()};
 * - {@see MultiRunner::runAndWaitForTheFirstNthResults()};
 * - {@see MultiRunner::runAndForget()}.
 *
 * Its children must implement creating instances of the processes
 * and adding them to the process queue.
 *
 * @psalm-api
 *                              }
 */
abstract class MultiRunner
{
    protected const STDIN = 0;
    protected const STDOUT = 1;
    protected const STDERR = 2;

    /**
     * @var string|null The initial working dir for the command or null.
     */
    protected ?string $cwd = null;

    /**
     * @var array<string, mixed>|null An array with the environment variables
     *                                for the command that will be run, or null.
     */
    protected ?array $envVars = null;

    /**
     * @var integer Maximum number of parallel processes running simultaneously.
     */
    private int $maxNumberParallelProcesses;

    /**
     * @var array<string, ProcessInQueueData> Array with processes to run.
     */
    private array $processesQueue = [];

    /**
     * @var array<string, RunningProcessData> Array with running processes and their data.
     */
    private array $runningProcesses = [];

    /**
     * @var OsCommandsWrapper Reference to OsCommandWrapper's instance.
     */
    protected OsCommandsWrapper $osCommandsWrapper;

    /**
     * Just set up {@link $maxNumberParallelProcesses} and
     * {@link $osCommandsWrapper}
     *
     * @param integer $maxNumberParallelProcesses Maximum number of parallel processes running simultaneously.
     * @param OsCommandsWrapper|null $osCommandsWrapper Reference to OsCommandWrapper's instance or null.
     */
    public function __construct(int $maxNumberParallelProcesses, OsCommandsWrapper $osCommandsWrapper = null)
    {
        $this->maxNumberParallelProcesses = $maxNumberParallelProcesses;
        $this->osCommandsWrapper = $osCommandsWrapper ?? new OsCommandsWrapper();
    }

    /**
     * Runs all processes in the {@see $processesQueue}
     * and forget about them.
     *
     * There must be no shell redirection in the commandLine field
     * in any {@see ProcessInQueueData} in {@see $processQueue}.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @return void
     * @throws RuntimeException If something goes wrong
     *                          when a process is started or a timeout occurs.
     * @psalm-api
     */
    public function runAndForget(int $waitTime): void
    {
        if ($this->maxNumberParallelProcesses >= count($this->processesQueue) && $this->isCwdOrEnvVarsNull()) {
            $this->justRunAndForget();
            return;
        }
        $this->runAndWaitForResults($waitTime);
    }

    /**
     * Runs all processes in the {@see $processesQueue} and
     * waits for all process has finished
     * and their results are returned or the timeout occurs.
     *
     * @param integer $waitTime             Seconds to wait for results.
     * @return array<string, ProcessResults>
     * @throws RuntimeException If something goes wrong
     *                          when a process is started or a timeout occurs.
     *
     * @SuppressWarnings(PHPMD.CountInLoopExpression)
     * @psalm-api
     */
    public function runAndWaitForResults(int $waitTime): array
    {
        $timeLimit = time() + $waitTime;

        $results = [];

        while (count($this->processesQueue) > 0) {
            $this->runNextBatch($timeLimit);
            $results = $results + $this->getResultsAndCloseCompletedProcesses($timeLimit);
        }

        while (count($this->runningProcesses) > 0) {
            $this->throwExceptionIfTimeLimitExceeded($timeLimit);
            $results = $results + $this->getResultsAndCloseCompletedProcesses($timeLimit);
        }

        return $results;
    }

    /**
     * Runs all processes in the {@see $processesQueue} and waits for
     * until the $resultsNumberToAwait processes have finished
     * and their results are returned or the timeout occurs.
     *
     * @param integer $waitTime             Seconds to wait for results.
     * @param integer $resultsNumberToAwait How many finished processes to await.
     * @return array<string, ProcessResults>
     * @throws RuntimeException If something goes wrong when a process is started or a timeout occurs.
     *
     * @SuppressWarnings(PHPMD.CountInLoopExpression)
     * @psalm-api
     */
    public function runAndWaitForTheFirstNthResults(int $waitTime, int $resultsNumberToAwait): array
    {
        $timeLimit = time() + $waitTime;

        $results = [];

        while (count($this->processesQueue) > 0) {
            $this->runNextBatch($timeLimit);
            $results = $results + $this->getResultsAndCloseCompletedProcesses($timeLimit);
            if (count($results) >= $resultsNumberToAwait) {
                return $results;
            }
        }

        while (count($this->runningProcesses) > 0) {
            $results = $results + $this->getResultsAndCloseCompletedProcesses($timeLimit);
            if (count($results) >= $resultsNumberToAwait) {
                return $results;
            }
        }

        return $results;
    }

    /**
     * Throws an exception when the timeout occurs.
     *
     * @param integer $timeLimit Unix time at which the timeout occurs.
     * @return void
     * @throws RuntimeException If the timeout occurs.
     */
    protected function throwExceptionIfTimeLimitExceeded(int $timeLimit): void
    {
        $currentTime = time();
        if ($timeLimit <= $currentTime) {
            throw new RuntimeException('Timeout: current time ' . date('H:i:s.u', $currentTime) .
                '(' . $currentTime . '); limit time: ' . date('H:i:s.u', $timeLimit) . '(' . $timeLimit . ')');
        }
    }

    /**
     * Retrieving available results and closing completed processes
     * from {@see $runningProcesses}.
     *
     * @param integer $timeLimit Unix time at which the timeout occurs.
     * @return array<string, ProcessResults>
     * @throws RuntimeException If the timeout occurs.
     */
    protected function getResultsAndCloseCompletedProcesses(int $timeLimit): array
    {
        $results = [];
        foreach ($this->runningProcesses as $processId => $processData) {
            $this->throwExceptionIfTimeLimitExceeded($timeLimit);
            // Before we can get the status of a process
            // we should get the contents of stdout and stderr pipes
            // to avoid deadlocks if the process sends a lot of output.
            $processData->stdout = ($processData->stdout ?? '') .
                stream_get_contents($processData->pipes[self::STDOUT]);
            $processData->stderr = ($processData->stderr ?? '') .
                stream_get_contents($processData->pipes[self::STDERR]);
            $procStatus = proc_get_status($processData->process);
            // Because phpstan thinks proc_get_status can return false.
            /* @phpstan-ignore offsetAccess.nonOffsetAccessible */
            if ($procStatus['running'] === false) {
                // Thanks to https://www.php.net/manual/en/function.proc-close.php#83622.
                $exitCodeWhenClose = $this->closeProcess($processId, $processData);
                $exitCode = $procStatus["exitcode"] === -1 ? $exitCodeWhenClose : $procStatus["exitcode"];
                $results[$processId] = new ProcessResults(
                    $exitCode,
                    $processData->stdout,
                    $processData->stderr
                );
            }
        }
        return $results;
    }

    /**
     * Tries to run one process and
     * put its data to the {@see $runningProcesses}.
     *
     * @param string $processId     The identifier of the process
     *                              in the {@see $runningProcesses}.
     * @param string $command       The command line to run.
     * @param string|null $cwd      The initial working dir for the command or null.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @return void
     * @throws RuntimeException If something goes wrong while attempting to run a process.
     */
    protected function runProcess(string $processId, string $command, ?string $cwd, ?array $envVars): void
    {
        // Use named pipe (file) as STDERR pipe
        // to avoid several bugs when STDERR is an anonymous pipe
        // especially on Windows.
        if (($errorFile = tempnam(sys_get_temp_dir(), 'error_pipe')) === false) {
            throw new RuntimeException('Cannot create a file  for the error pipe.');
        }
        if (!$errorFileDescriptor = fopen($errorFile, 'r')) {
            throw new RuntimeException('Cannot open the error file ' . $errorFile . ' for reading');
        }

        $descriptors = array(
            self::STDIN  => ["pipe", "r"],
            self::STDOUT => ["pipe", "w"],
            self::STDERR => ["file", $errorFile, "a"],
        );
        $processData = new RunningProcessData();
        $process = proc_open(
            $command,
            $descriptors,
            $processData->pipes,
            $cwd,
            $envVars,
            ['bypass_shell' => true]
        );

        if ($process === false) {
            throw new RuntimeException("An error occurred during execution " . $command);
        }

        // Close STDIN immediately as we never use it.
        fclose($processData->pipes[self::STDIN]);
        stream_set_blocking($processData->pipes[self::STDOUT], false);
        $processData->pipes[self::STDERR] = $errorFileDescriptor;

        $processData->process = $process;
        $processData->files[] = $errorFile;
        $this->runningProcesses[$processId] = $processData;
    }

    /**
     * Calls {@see closeProcess()} for all processes
     * in the {@see $runningProcesses}
     */
    public function __destruct()
    {
        foreach ($this->runningProcesses as $processId => $processData) {
            $this->closeProcess($processId, $processData);
        }
    }

    /**
     * Returns the absolute path of the working directory
     * or null if $cwd is null.
     *
     * @param string|null $cwd The current working directory for processes to run.
     * @return string|null
     * @throws RuntimeException If $cwd isn't null and not found.
     */
    protected function checkAndNormalizeCWD(?string $cwd): ?string
    {
        if (empty($cwd)) {
            return $cwd;
        }
        $cwdRealPath = realpath($cwd);
        if ($cwdRealPath === false) {
            throw new RuntimeException("The directory " . $cwd . " not found");
        }
        return $cwdRealPath;
    }

    /**
     * Create a temporary directory as cwd
     * for {@see CodeMultiRunner} and {@see DiffCodeMultiRunner}
     * where a temporary script file will be created.
     *
     * If $baseFolder is null, the system temp directory is used.
     *
     * @param string|null $baseFolder   A directory where
     *                                  the temporary folder will be created.
     * @return void
     * @throws RuntimeException         If a temp directory cannot be created.
     *
     * @noinspection PhpDocMissingThrowsInspection phpStorm incorrectly detects the ErrorException
     *                                             from the anonymous function
     *                                             which is caught and replaced by RuntimeException.
     */
    protected function setupCwdForCode(?string $baseFolder): void
    {
        $baseFolder = $baseFolder ?? sys_get_temp_dir();

        // Set our own error handler to catch an error from mkdir().
        set_error_handler(
            function ($errorSeverityNum, $message, $file, $line): bool {
                if (error_reporting()) {
                    // @noinspection PhpUnhandledExceptionInspection
                    throw new ErrorException($message, 0, $errorSeverityNum, $file, $line);
                }
                return true;
            }
        );

        $workingFolder = dirname($baseFolder) . DIRECTORY_SEPARATOR . basename($baseFolder)
            . DIRECTORY_SEPARATOR . uniqid();
        try {
            $workingFolderCreated = mkdir($workingFolder, 0600, true);
        } catch (Throwable $t) {
            throw new RuntimeException("Cannot create the folder for processing " . $workingFolder .
                PHP_EOL . $t->getMessage());
        }
        if (!$workingFolderCreated) {
            throw new RuntimeException("Cannot create the folder for processing " . $workingFolder);
        }

        $workingFolderRealPath = realpath($workingFolder);
        if ($workingFolderRealPath === false) {
            throw new RuntimeException("Cannot create the folder for processing " . $workingFolder);
        }
        $this->cwd = $workingFolderRealPath;

        restore_error_handler();
    }

    /**
     * Tries to start the next batch of processes in the {@see $processesQueue}.
     *
     * @param integer $timeLimit Unix time at which the timeout occurs.
     * @return void
     * @throws RuntimeException When a time limit is exceeded
     *                          or a process cannot be executed.
     */
    protected function runNextBatch(int $timeLimit): void
    {
        $quantityProcessToRun = $this->maxNumberParallelProcesses - count($this->runningProcesses);
        foreach ($this->processesQueue as $processId => $processData) {
            $this->throwExceptionIfTimeLimitExceeded($timeLimit);
            if ($quantityProcessToRun <= 0) {
                break;
            }
            $command = $processData->commandLine;
            $cwd = $processData->cwd;
            $envVars = $processData->envVars;
            $this->runProcess($processId, $command, $cwd, $envVars);
            unset($this->processesQueue[$processId]);
            $quantityProcessToRun--;
        }
    }


    /**
     * Forms command line to run a process
     *
     * @param string $program The name or the full path of the program to run.
     * @param string[] $programOpts The arguments to the program.
     * @param string $scriptFullPath The full path to the script to execute
     *                               or the empty string.
     * @param string[] $scriptArgs The arguments of the scripts.
     * @return string The formed command line.
     */
    protected function formCommandLine(
        string $program,
        array $programOpts,
        string $scriptFullPath = '',
        array $scriptArgs = []
    ): string {
        $commandLine = $this->osCommandsWrapper->escapeArg($program);
        foreach ($programOpts as $programOption) {
            $commandLine .= ' ' . $this->osCommandsWrapper->escapeArg($programOption);
        }
        if (!empty($scriptFullPath)) {
            $quotesForUserArgs = $this->quotesForUserArgs($program);
            $commandLine .= ' ' . $quotesForUserArgs;
            $commandLine .= ' ' . $this->osCommandsWrapper->escapeArg($scriptFullPath);
            foreach ($scriptArgs as $arg) {
                $commandLine .= ' ' . $this->osCommandsWrapper->escapeArg($arg);
            }
            $commandLine .= $quotesForUserArgs;
        }
        // On Windows, we always run a process bypassing the shell,
        // so there is no need to escape the whole command  line.
        return $commandLine;
    }

    /**
     * Adds a process in the processes queue
     *
     * @param string $processId The identifier of the process
     *                          in the {@see $runningProcesses}.
     * @param string $commandLine The command line to run.
     * @param string|null $cwd The initial working dir for the command or null.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @return void
     */
    protected function addProcessInQueue(string $processId, string $commandLine, ?string $cwd, ?array $envVars): void
    {
        $processInQueueData = new ProcessInQueueData($commandLine, $cwd, $envVars);
        $this->processesQueue[$processId] = $processInQueueData;
    }

    /**
     * Returns quotation marks for user arguments if necessary.
     *
     * To the best of our knowledge, only cmd on Windows
     * needs to enclose user arguments in quotes
     *
     * @param string $interpreter The name of the interpreter.
     * @return string
     */
    protected function quotesForUserArgs(string $interpreter): string
    {
        return $interpreter === 'cmd' ? '"' : '';
    }

    /**
     * Returns the script file extension for the given interpreter.
     *
     * To the best of our knowledge, only cmd on Windows
     * requires the 'cmd' extension to run the script.
     *
     * @param string $interpreter The name of the interpreter.
     * @return string
     */
    protected function scriptFileExtension(string $interpreter): string
    {
        return $interpreter === 'cmd' ? '.cmd' : '';
    }

    /**
     * This method executes all the commands in the process queue
     * in the fastest possible way and forget about them.
     *
     * It should only be called if $cwd is null and
     * $maxParallelProcesses >= count($this->processesQueue).
     *
     * @return void
     * @throws RuntimeException If some process cannot be launched.
     */
    protected function justRunAndForget(): void
    {
        foreach ($this->processesQueue as $processData) {
            $command = $processData->commandLine;
            if ($this->osCommandsWrapper->isWindows()) {
                // Based on https://stackoverflow.com/a/17682046. Thank you jeb!
                if (!$fp = popen("start \"\" /B CALL " . $command . ' 1>Nul 2>&1', "r")) {
                    throw new RuntimeException('Cannot run ' . $command);
                }
                pclose($fp);
            } else {
                exec($command . " > /dev/null &");
            }
        }
    }

    /**
     * Check to see if there are any cwd or envVars parameters
     * in the processesQueue that are not null.
     *
     * @return boolean
     */
    protected function isCwdOrEnvVarsNull(): bool
    {
        foreach ($this->processesQueue as $processParams) {
            if (!empty($processParams->cwd) || !empty($processParams->envVars)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Closes all open pipes and files of the process,
     * terminates the process, deletes its entry in the
     * {@see $runningProcesses} and returns the exit code of the process.
     *
     * @param string $processId The identifier of the process
     *                          in the {@see $runningProcesses}.
     * @param RunningProcessData $processData The data of the running process.
     * @return integer The exit code of the process.
     */
    protected function closeProcess(string $processId, RunningProcessData $processData): int
    {
        foreach ($processData->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        foreach ($processData->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $exitCode = proc_close($processData->process);
        unset($this->runningProcesses[$processId]);
        return $exitCode;
    }

    /**
     * Returns the name of the program with which it can be launched.
     *
     * @param string $program The name or the full path of the program.
     * @param string|null $cwd The working directory where the program runs.
     * @return string The name of the program with which it can be launched.
     * @throws RuntimeException If the program not found.
     */
    protected function checkAndNormaliseProgram(string $program, ?string $cwd): string
    {
        if ($this->osCommandsWrapper->programExists($program) === 0) {
            return $program;
        }
        if (!empty($cwd) && file_exists($cwd)) {
            $programInCwd = $cwd . DIRECTORY_SEPARATOR . $program;
            if ($this->osCommandsWrapper->programExists($programInCwd) === 0) {
                return $programInCwd;
            }
        }

        throw new RuntimeException('The program ' . $program . ' not found');
    }

    /**
     * Delete the $this->cwd and all its contents
     *
     * @return void
     */
    protected function clearAndDeleteCwd()
    {
        if (!empty($this->cwd) && file_exists($this->cwd)) {
            $this->osCommandsWrapper->removeDirRecursive($this->cwd);
        }
    }
}
