<?php

/**
 * MultiRunner final classes: ProgramMultiRunner class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use RuntimeException;
use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

/**
 * Class for running multiple instances of a program simultaneously
 * in parallel in the background and possibly getting their results (outputs).
 *
 * @psalm-api
 */
final class ProgramMultiRunner extends MultiRunner
{
    /**
     * @var string The full path to the executable of the program.
     */
    protected string $program;

    /**
     * @var string[] Additional arguments to a program.
     */
    protected array $programOpts;

    /**
     * Creates an object to run multiple versions of the program with different arguments.
     *
     * @param integer $maxNumberParallelProcesses Maximum number of parallel processes running simultaneously.
     * @param string $program The full path to the executable of the program.
     * @param array<string> $programOpts Additional arguments to a program.
     * @param string|null $cwd The initial working dir for the command or null.
     * @param array<string, mixed>|null $envVars Array of environment variables
     *                                           to pass to proc_open.
     * @param OsCommandsWrapper|null $osCommandsWrapper An object to work with os.
     * @throws RuntimeException If a cwd cannot be set up or a program is not found.
     */
    public function __construct(
        int $maxNumberParallelProcesses,
        string $program,
        array $programOpts = [],
        ?string $cwd = null,
        ?array $envVars = null,
        OsCommandsWrapper $osCommandsWrapper = null
    ) {
        parent::__construct($maxNumberParallelProcesses, $osCommandsWrapper);
        $this->cwd = $this->checkAndNormalizeCWD($cwd);
        $this->program = $this->checkAndNormaliseProgram($program, $this->cwd);
        $this->programOpts = $programOpts;
        $this->envVars = $envVars;
    }

    /**
     * Adding a process to the process queue.
     *
     * @param string $processId Any string that identifies the process.
     * @param string ...$args Additional arguments to the instance of the process.
     *
     * @return void
     */
    public function addProcess(string $processId, string ...$args): void
    {
        $commandLine = $this->formCommandLine(
            $this->program,
            $this->programOpts + $args
        );
        $this->addProcessInQueue($processId, $commandLine, $this->cwd, $this->envVars);
    }
}
