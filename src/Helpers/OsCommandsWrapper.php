<?php

/**
 * MultiRunner helper class: OsCommandsWrapper class
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Helpers;

use Error;
use InvalidArgumentException;
use RuntimeException;

/**
 * The class with methods to work with OS dependent functions.
 *
 * @psalm-api
 */
class OsCommandsWrapper
{
    /**
     * Find out if the current system is Windows
     *
     * @return boolean
     */
    public function isWindows(): bool
    {
        if (PHP_OS == 'WINNT' || PHP_OS == 'WIN32') {
            return true;
        }
        return false;
    }

    /**
     * Delete $dir and all its contents
     *
     * @param string $dir An absolute path to the directory to be deleted.
     * @return void
     */
    public function removeDirRecursive(string $dir): void
    {
        if ($this->isWindows()) {
            exec(sprintf("rd /s /q %s", escapeshellarg($dir)));
            return;
        }
        exec(sprintf("rm -rf %s", escapeshellarg($dir)));
    }

    /**
     * Find out if the program/interpreter exists
     * in the current system and can therefore be started.
     *
     * @param string $program The name or the full path of the program.
     * @return integer
     */
    public function programExists(string $program): int
    {
        $exitCodeSuccess = 0;
        if (file_exists($program)) {
            return $exitCodeSuccess;
        }
        if ($this->isWindows()) {
            exec('where ' . $program . ' 1>nul 2>&1', $output, $exitCode);
            return $exitCode;
        }
        exec('which -a ' . $program . ' 1>/dev/null 2>&1', $output, $exitCode);
        return $exitCode;
    }

    /**
     * Escapes $value as a commandline argument for different OS the right way
     *
     * @param string $arg A string to escape.
     * @return string
     * @throws InvalidArgumentException If argument isn't a valid UTF-8 string
     *                                  or there is an invalid byte.
     * @throws Error If there is PCRE error.
     */
    public function escapeArg(string $arg): string
    {
        if ($this->isWindows()) {
            return $this->escapeArgWin32($arg);
        }
        return escapeshellarg($arg);
    }

    /**
     * Escapes an entire command line string for shell.
     *
     * For Windows escapes special characters by ^.
     *
     * @param string $cmd A string (a command line) to escape.
     * @return string
     * @psalm-api
     */
    public function escapeCmd(string $cmd): string
    {
        if ($this->isWindows()) {
            return $this->escapeCmdWin32($cmd);
        }
        return escapeshellcmd($cmd);
    }

    /**
     * Escapes $value as a commandline argument for Windows the right way
     *
     * ({@link https://www.php.net/manual/ru/function.escapeshellarg.php#123718})
     * @param string $value An argument to escape.
     * @return string
     * @throws InvalidArgumentException If argument isn't a valid UTF-8 string
     *                                  or there is an invalid byte.
     * @throws Error If there is PCRE error.
     */
    public function escapeArgWin32(string $value): string
    {
        static $expr = '(
                [\x00-\x20\x7F"] # control chars, whitespace or double quote
                | \\\\++ (?=("|$)) # backslashes followed by a quote or at the end
            )ux';

        if ($value === '') {
            return '""';
        }
        $quote = false;

        $replacer = function (array $match) use ($value, &$quote): string {
            switch ($match[0][0]) { // Only inspect the first byte of the match.
                case '"': // Double quotes are escaped and must be quoted.
                    $match[0] = '\\"';
                    // Fall-through is intentional here.
                case ' ':
                case "\t": // Spaces and tabs are ok but must be quoted.
                    $quote = true;
                    return $match[0];
                case '\\': // Matching backslashes are escaped if quoted.
                    return $match[0] . $match[0];
                default:
                    throw new InvalidArgumentException(
                        sprintf(
                            "Invalid byte at offset %d: 0x%02X",
                            strpos($value, $match[0]),
                            ord($match[0])
                        )
                    );
            }
        };

        $escaped = preg_replace_callback($expr, $replacer, $value);

        if ($escaped === null) {
            throw preg_last_error() === PREG_BAD_UTF8_ERROR
                ? new InvalidArgumentException("Invalid UTF-8 string")
                : new Error("PCRE error: " . preg_last_error());
        }

        return $quote // Only quote when needed.
            ? '"' . $escaped . '"'
            : $value;
    }

    /**
     * Escape cmd.exe metacharacters with ^
     *
     * from {@link https://www.php.net/manual/ru/function.escapeshellarg.php#123718}
     *
     * @param string $value A string to escape for Win cmd.
     * @return string
     * @throws RuntimeException If there is error when escaping {@see $value}.
     */
    public function escapeCmdWin32(string $value): string
    {
        $result = preg_replace('([()%!^"<>&|])', '^$0', $value);
        if (is_null($result)) {
            throw new RuntimeException("There was error while escaping string " . $value);
        }
        return $result;
    }
}
