<?php

/**
 * MultiRunner final class: DiffCodeMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use RuntimeException;
use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

/**
 * Class for running different codes simultaneously
 * in parallel in the background and possibly getting
 * their results (outputs).
 *
 * @psalm-api
 */
final class DiffCodeMultiRunner extends MultiRunner
{
    /**
     * Creates an object to run different codes.
     *
     * @param integer $maxNumberParallelProcesses Maximum number of parallel processes running simultaneously.
     * @param string|null $baseFolder The folder where to make a temporary folder for temporary scripts.
     * @param OsCommandsWrapper|null $osCommandsWrapper An object to work with os.
     * @throws RuntimeException If a cwd cannot be set up.
     */
    public function __construct(
        int $maxNumberParallelProcesses,
        ?string $baseFolder = null,
        ?OsCommandsWrapper $osCommandsWrapper = null
    ) {
        parent::__construct($maxNumberParallelProcesses, $osCommandsWrapper);

        $this->setupCwdForCode($baseFolder);
    }

    /**
     * Add a code with an interpreter to the process queue as a new process.
     *
     * @param string $processId The identifier of the process
     *                          in the {@see $runningProcesses}.
     * @param string $scriptText The code to run.
     * @param string $interpreter The name of the interpreter or the full path to its executable.
     * @param array<string> $interpreterArgs Additional arguments to an interpreter.
     * @param array<string>|null $envVars Array of environment variables
     *                                    to pass to proc_open.
     * @param string ...$args Additional arguments to the instance of the process.
     *
     * @return void
     * @throws RuntimeException If an interpreter is not found or
     *                          the temp script for processing cannot be created.
     */
    public function addProcess(
        string $processId,
        string $scriptText,
        string $interpreter = 'php',
        array $interpreterArgs = [],
        ?array $envVars = null,
        string ...$args
    ): void {
        if ($this->osCommandsWrapper->programExists($interpreter) !== 0) {
            throw new RuntimeException('Interpreter ' . $interpreter . ' not found');
        }
        // phpcs:disable
        /** @psalm-suppress PossiblyNullOperand */
        // phpcs:enable
        $mainScriptFullPath = $this->cwd . DIRECTORY_SEPARATOR .
            $processId . '_script' . $this->scriptFileExtension($interpreter);
        if (file_put_contents($mainScriptFullPath, $scriptText) === false) {
            throw new RuntimeException("Cannot create the main script for processing " . $mainScriptFullPath);
        }

        $commandLine = $this->formCommandLine($interpreter, $interpreterArgs, $mainScriptFullPath, $args);
        $this->addProcessInQueue($processId, $commandLine, $this->cwd, $envVars);
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
