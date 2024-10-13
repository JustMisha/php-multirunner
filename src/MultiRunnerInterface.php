<?php

/**
 * MultiRunner main interface: MultiRunnerInterface
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner;

use JustMisha\MultiRunner\DTO\ProcessResults;

/**
 * The interface defines methods to run multiple processes in parallel
 * and possibly getting their results (outputs).
 *
 */
interface MultiRunnerInterface
{
    /**
     * Runs all processes and waits for all process has finished
     * and their results are returned or the timeout occurs.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @return array<string, ProcessResults>
     *
     * @psalm-api
     */
    public function runAndWaitForResults(int $waitTime): array;

    /**
     * Runs all processes and forget about them.
     *
     * There must be no shell redirection in the commandLine field.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @return void
     *
     * @psalm-api
     */
    public function runAndForget(int $waitTime): void;

    /**
     * Runs all processes and waits for
     * until the $resultsNumberToAwait processes have finished
     * and their results are returned or the timeout occurs.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @param integer $resultsNumberToAwait How many finished processes to await.
     * @return array<string, ProcessResults>
     *
     * @psalm-api
     */
    public function runAndWaitForTheFirstNthResults(int $waitTime, int $resultsNumberToAwait): array;
}
