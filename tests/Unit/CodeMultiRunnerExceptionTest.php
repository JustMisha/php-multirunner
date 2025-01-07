<?php

/**
 * MultiRunner test classes: CodeMultiRunnerExceptionTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\CodeMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * Check whether CodeMultiRunner's constructor throws exceptions
 * in the right cases.
 *
 */
class CodeMultiRunnerExceptionTest extends BaseTestCase
{
    /**
     * Prepare a test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        parent::setUp();
    }

    /**
     * Check CodeMultiRunner's constructor throws an exception
     * if an interpreter is not found.
     *
     * @return void
     */
    public function testThrowExceptionIfInterpreterNotFound(): void
    {
        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . '/runtime';
        $this->expectExceptionMessage('Interpreter Hahaha not found');
        new CodeMultiRunner(
            5,
            '$result = "Hahaha";',
            'Hahaha',
            [],
            $baseFolder,
            null,
            null
        );
    }

    /**
     * Check whether CodeMultiRunner's constructor throws an exception
     * when there are no rights to baseFolder
     *
     * To make it work in docker, it must be started
     * with "--cap-add LINUX_IMMUTABLE" argument
     *
     * @return void
     */
    public function testThrowExceptionIfFolderCannotBeCreated(): void
    {
        if ($this->isWindows()) {
            // For the GitHub action, skip the test
            // if it runs as the admin user.
            if (get_current_user() === '"runneradmin') {
                $this->assertTrue(true);
                return;
            }
            $baseFolder = dirname('c:\Windows\runtime');
        } else {
            $baseFolder = dirname('/tmp/runtime/' . time());
            if (!file_exists($baseFolder)) {
                mkdir($baseFolder);
            }
            exec(sprintf("chattr +i %s", escapeshellarg($baseFolder)));
        }

        $this->expectExceptionMessage('Cannot create the folder for processing');
        $this->expectException('RuntimeException');
        new CodeMultiRunner(
            5,
            '<?php echo "Hahaha";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );

        if (!$this->isWindows()) {
            exec(sprintf("chattr -i %s", escapeshellarg($baseFolder)));
            exec(sprintf("rm -rf %s", escapeshellarg($baseFolder)));
        }
    }

    /**
     * Check whether CodeMultiRunner's constructor throws an exception
     * if a main script cannot be saved.
     *
     * @return void
     */
    public function testThrowsExceptionIfMainScriptCannotBeSaved(): void
    {
        global $mockFilePutContents;
        $mockFilePutContents = true;

        $baseFolder = $this->runtimeFullPath;
        $this->expectExceptionMessage('Cannot create the main script for processing');
        new CodeMultiRunner(
            50,
            '<?php echo "Hahaha";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );
        $mockFilePutContents = false;
    }

    /**
     * Check whether CodeMultiRunner's constructor throws an exception
     * if a base folder cannot be created.
     *
     * @return void
     */
    public function testThrowsExceptionIfBaseFolderCannotBeCreated(): void
    {
        global $mockMkdir;
        $mockMkdir = true;

        $baseFolder = $this->runtimeFullPath;
        $this->expectExceptionMessage('Cannot create the folder for processing');
        new CodeMultiRunner(
            50,
            '<?php echo "Hahaha";',
            'php',
            [],
            $baseFolder,
            null,
            null
        );
        $mockMkdir = false;
    }
}
