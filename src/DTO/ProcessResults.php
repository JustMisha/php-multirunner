<?php

/**
 * MultiRunner DTO: ProcessResults class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\DTO;

/**
 * Just LocalDTO class for keeping data of the completed process.
 *
 * @psalm-api
 */
class ProcessResults
{
    /**
     * @var integer An exit code of the process.
     */
    public int $exitCode;

    /**
     * @var string A standard output of the process.
     */
    public string $stdout;

    /**
     * @var string A standard error output of the process.
     */
    public string $stderr;

    /**
     * @param integer $exitCode An exit code of the process.
     * @param string $stdout A standard output of the process.
     * @param string $stderr A standard error output of the process.
     */
    public function __construct(int $exitCode, string $stdout, string $stderr)
    {
        $this->exitCode = $exitCode;
        $this->stdout   = $stdout;
        $this->stderr   = $stderr;
    }
}
