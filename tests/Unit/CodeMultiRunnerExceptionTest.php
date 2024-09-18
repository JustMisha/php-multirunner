<?php

namespace JustMisha\MultiRunner\Tests\Unit;

use Exception;
use JustMisha\MultiRunner\CodeMultiRunner;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class CodeMultiRunnerExceptionTest extends BaseTestCase
{
    protected function setUp(): void
    {
        global $mockMkdir;
        $mockMkdir = false;
        global $mockFilePutContents;
        $mockFilePutContents = false;
        $this->clearRuntimeFolder();
    }

    public function testThrowExceptionIfInterpreterNotFound(): void
    {
        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . '/runtime';
        $this->expectExceptionMessage('Interpreter Hahaha not found');
        $bc = new CodeMultiRunner(5, '$result = "Hahaha";', 'Hahaha', [], $baseFolder, null, null);
    }

    /**
     * Check whether constructor throws an exception
     * when there are no rights to baseFolder
     *
     * To make it work in docker, it must be started
     * with "--cap-add LINUX_IMMUTABLE" argument
     *
     * @return void
     * @throws Exception
     */
    public function testThrowExceptionIfFolderCannotBeCreated(): void
    {
        if ($this->isWindows()) {
            $baseFolder = dirname('c:\Windows\runtime');
        } else {
            $baseFolder = dirname('/tmp/runtime/'.time());
            if (!file_exists($baseFolder)) {
                mkdir($baseFolder);
            }
            exec(sprintf("chattr +i %s", escapeshellarg($baseFolder)));
        }

        $this->expectExceptionMessage('Cannot create the folder for processing');
        $this->expectException('RuntimeException');
        $bc = new CodeMultiRunner(5, '<?php echo "Hahaha";', 'php', [], $baseFolder, null, null);

        if (!$this->isWindows()) {
            exec(sprintf("chattr -i %s", escapeshellarg($baseFolder)));
            exec(sprintf("rm -rf %s", escapeshellarg($baseFolder)));
        }
    }

    public function testThrowsExceptionIfMainScriptCannotBeSaved(): void
    {
        global $mockFilePutContents;
        $mockFilePutContents = true;

        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';
        $this->expectExceptionMessage('Cannot create the main script for processing');
        $bc = new CodeMultiRunner(50, '<?php echo "Hahaha";', 'php', [], $baseFolder, null, null);
        $mockFilePutContents = false;
    }

    public function testThrowsExceptionIfBaseFolderCannotBeCreated(): void
    {
        global $mockMkdir;
        $mockMkdir = true;

        $baseFolder = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'runtime';
        $this->expectExceptionMessage('Cannot create the folder for processing');
        $bc = new CodeMultiRunner(50, '<?php echo "Hahaha";', 'php', [], $baseFolder, null, null);
        $mockMkdir = false;
    }
}