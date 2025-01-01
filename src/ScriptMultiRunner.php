<?php

/**
 * MultiRunner final classes: ScriptMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use RuntimeException;
use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

/**
 * Class for running multiple instances of a script simultaneously
 * with different arguments in parallel in the background
 * and possibly getting their results (outputs).
 *
 * @psalm-api
 */
final class ScriptMultiRunner extends MultiRunner
{
    /**
     * @var string Full path to main script.
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
     * Creates an object to run multiple versions of the same script with different arguments.
     *
     * @param integer $maxNumberParallelProcesses Maximum number of parallel processes running simultaneously.
     * @param string $mainScriptFullPath Full script path.
     * @param string|null $cwd The initial working dir for the command or null.
     * @param string $interpreter The name of the interpreter or the full path to its executable.
     * @param array<string> $interpreterArgs Additional arguments to an interpreter.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @param OsCommandsWrapper|null $osCommandsWrapper An object to work with os.
     *
     * @throws RuntimeException If a cwd cannot be set up or an interpreter is not found.
     */
    public function __construct(
        int $maxNumberParallelProcesses,
        string $mainScriptFullPath,
        ?string $cwd = null,
        string $interpreter = 'php',
        array $interpreterArgs = [],
        ?array $envVars = null,
        ?OsCommandsWrapper $osCommandsWrapper = null
    ) {
        parent::__construct($maxNumberParallelProcesses, $osCommandsWrapper);

        $this->cwd = $this->checkAndNormalizeCWD($cwd);
        $this->interpreter = $this->checkAndNormaliseProgram($interpreter, $this->cwd);
        $this->interpreterArgs = $interpreterArgs;
        $this->mainScriptFullPath = $mainScriptFullPath;
        $this->envVars = $envVars;
    }

    /**
     * Adds a process to the process queue
     *
     * @param string $processId Any string that identifies the process.
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
}
