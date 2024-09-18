<?php

/**
 * MultiRunner DTO: ProcessInQueueData class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\DTO;

/**
 * Just LocalDTO class for keeping data of each process in queue
 */
class ProcessInQueueData
{
    /**
     * @var string Fully formed command line to run via proc_open.
     */
    public string $commandLine;

    /**
     * @var string|null Working directory to pass to proc_open.
     */
    public ?string $cwd;

    /**
     * @var array<string, mixed>|null Array of environment variables
     *                                to pass to proc_open.
     */
    public ?array $envVars;

    /**
     * Create ProcessInQueueData object
     *
     * @param string $commandLine Fully formed command line to run via proc_open.
     * @param string|null $cwd Working directory to pass to proc_open.
     * @param array<string, mixed>|null $envVars An array of environment variables to pass to proc_open.
     */
    public function __construct(string $commandLine, ?string $cwd, ?array $envVars)
    {
        $this->commandLine = $commandLine;
        $this->cwd = $cwd;
        $this->envVars = $envVars;
    }
}
