<?php

namespace JustMisha\MultiRunner;

use JustMisha\MultiRunner\DTO\ProcessResults;

/**
 * @method addProcess
 */
interface MultiRunnerInterface
{
    /**
     * Runs all processes and waits for all process has finished
     * and their results are returned or the timeout occurs.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @return array<string, ProcessResults>
     */
    public function runAndWaitForResults(int $waitTime): array;

    /**
     * Runs all processes and forget about them.
     *
     * There must be no shell redirection in the commandLine field.
     *
     * @param integer $waitTime Seconds to wait for results.
     * @return void
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
     */
    public function runAndWaitForTheFirstNthResults(int $waitTime, int $resultsNumberToAwait): array;
}
