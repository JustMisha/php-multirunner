<?php

/**
 * MultiRunner DTO: RunningProcessData class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\DTO;

/**
 * Just LocalDTO class for keeping data of each running process
 *
 * phpcs:disable
 * @psalm-suppress MissingConstructor
 * phpcs:enable
 */
class RunningProcessData
{
    /**
     * @var resource A resource representing the process.
     */
    public $process;

    /**
     * @var array<int, resource> An array holding a reference
     *                           to all pipes used by a process.
     */
    public array $pipes = [];

    /**
     * @var string[] An array of filenames used by a process.
     */
    public array $files = [];

    /**
     * @var string A buffer for holding data from the stdout stream
     *             before a process is terminated.
     */
    public string $stdout;

    /**
     * @var string A buffer for holding data from the stderr stream
     *             before a process is terminated.
     */
    public string $stderr;
}
