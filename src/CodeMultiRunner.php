<?php

/**
 * MultiRunner final class: CodeMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use RuntimeException;
use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

/**
 * Class for running multiple instances of a code simultaneously
 * with different arguments in parallel in the background
 * and possibly getting their results (outputs).
 *
 * @psalm-api
 */
final class CodeMultiRunner extends MultiRunner
{
    /**
     * @var string Full path to main script
     */
    protected string $mainScriptFullPath;

    /**
     * @var string The name of the interpreter or the full path to its executable.
     */
    protected string $interpreter;

    /**
     * @var string[] Additional arguments to an interpreter.
     */
    protected array $interpreterArgs = [];

    /**
     * Creates an object to run multiple versions of the same code with different arguments.
     *
     * @param integer $maxNumberParallelProcesses Maximum number of parallel processes running simultaneously.
     * @param string $scriptText The code to run.
     * @param string $interpreter The name of the interpreter or the full path to its executable.
     * @param array<string> $interpreterArgs Additional arguments to an interpreter.
     * @param string|null $baseFolder The folder where to make a temporary folder for temporary scripts.
     * @param array<string>|null $envVars Array of environment variables
     *                                    to pass to proc_open.
     * @param OsCommandsWrapper|null $osCommandsWrapper An object to work with os.
     * @throws RuntimeException If the interpreter can't be found,
     *                          or a temp folder for a temp script can't be created,
     *                          or the script can't be created.
     */
    public function __construct(
        int $maxNumberParallelProcesses,
        string $scriptText,
        string $interpreter = 'php',
        array $interpreterArgs = [],
        ?string $baseFolder = null,
        ?array $envVars = null,
        ?OsCommandsWrapper $osCommandsWrapper = null
    ) {
        parent::__construct($maxNumberParallelProcesses, $osCommandsWrapper);

        $this->setupInterpreter($interpreter, $interpreterArgs);

        $this->setupCwdForCode($baseFolder);
        // phpcs:disable
        /** @psalm-suppress PossiblyNullOperand */
        // phpcs:enable
        $this->mainScriptFullPath = $this->cwd . DIRECTORY_SEPARATOR
            . 'main' . $this->scriptFileExtension($interpreter);
        if (file_put_contents($this->mainScriptFullPath, $scriptText) === false) {
            throw new RuntimeException("Cannot create the main script for processing " . $this->mainScriptFullPath);
        }
        $this->envVars = $envVars;
    }

    /**
     * Adds a process to the process queue
     *
     * @param string $processId The identifier of the process
     *                          in the {@see $runningProcesses}.
     * @param string ...$args Additional arguments to the instance of the process.
     *
     * @return void
     */
    public function addProcess(string $processId, string ...$args): void
    {
        $commandLine = $this->formCommandLine(
            $this->interpreter,
            $this->interpreterArgs,
            $this->mainScriptFullPath,
            $args
        );
        $this->addProcessInQueue($processId, $commandLine, $this->cwd, $this->envVars);
    }

    /**
     * @param string $interpreter The name of the interpreter or the full path to its executable.
     * @param array<string> $interpreterArgs Additional arguments to the instance of the process.
     * @return void
     * @throws RuntimeException If the interpreter is not found.
     */
    protected function setupInterpreter(string $interpreter, array $interpreterArgs = []): void
    {
        if ($this->osCommandsWrapper->programExists($interpreter) !== 0) {
            throw new RuntimeException('Interpreter ' . $interpreter . ' not found');
        }
        $this->interpreter = $interpreter;
        $this->interpreterArgs = $interpreterArgs;
    }

    /**
     * Close all pipes and delete the $this->cwd and all its contents
     */
    public function __destruct()
    {
        parent::__destruct();
        $this->clearAndDeleteCwd();
    }
}
