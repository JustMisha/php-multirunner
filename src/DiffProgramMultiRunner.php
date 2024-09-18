<?php

/**
 * MultiRunner final class: DiffProgramMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

/**
 * The class for running different programs simultaneously
 * in parallel in the background and possibly getting their results (outputs).
 *
 * @psalm-api
 */
final class DiffProgramMultiRunner extends MultiRunner
{
    /**
     * Add a program to the process queue as a new process.
     *
     * @param string $processId The identifier of the process
     *                              in the {@see $runningProcesses}.
     * @param string $program The name or the full path of the program.
     * @param array<string> $programOpts The arguments to the program.
     * @param string|null $cwd The initial working dir for the command or null.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @param string ...$args Additional arguments to the instance of the process.
     *
     * @return void
     */
    public function addProcess(
        string $processId,
        string $program,
        array $programOpts = [],
        ?string $cwd = null,
        ?array $envVars = null,
        string ...$args
    ): void {
        $cwd = $this->checkAndNormalizeCWD($cwd);
        $program = $this->checkAndNormaliseProgram($program, $cwd);
        $commandLine = $this->formCommandLine(
            $program,
            $programOpts + $args
        );
        $this->addProcessInQueue($processId, $commandLine, $cwd, $envVars);
    }
}
