<?php

/**
 * MultiRunner final class: DiffScriptMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use RuntimeException;

/**
 * The class for running different scripts simultaneously in parallel
 * in the background and possibly getting their results (outputs).
 *
 * @psalm-api
 */
final class DiffScriptMultiRunner extends MultiRunner
{
    /**
     * Add a script to the process queue as a new process.
     *
     * @param string $processId     The identifier of the process
     *                              in the {@see $runningProcesses}.
     * @param string $mainScriptFullPath The full path to the script to run.
     * @param string|null $cwd The initial working dir for the command or null.
     * @param string $interpreter The name or the full path of the interpreter.
     * @param array<string> $interpreterArgs Array of the arguments to the interpreter.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @param string ...$args Additional arguments to the instance of the process.
     *
     * @return void
     * @throws RuntimeException If $cwd isn't null and not found
     *                          or $program not found.
     */
    public function addProcess(
        string $processId,
        string $mainScriptFullPath,
        ?string $cwd = null,
        string $interpreter = 'php',
        array $interpreterArgs = [],
        ?array $envVars = null,
        string ...$args
    ): void {
        $cwd = $this->checkAndNormalizeCWD($cwd);
        $interpreter = $this->checkAndNormaliseProgram($interpreter, $cwd);
        $commandLine = $this->formCommandLine(
            $interpreter,
            $interpreterArgs,
            $mainScriptFullPath,
            $args
        );
        $this->addProcessInQueue($processId, $commandLine, $cwd, $envVars);
    }
}
