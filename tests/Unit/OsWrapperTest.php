<?php

/**
 * MultiRunner test classes: OsWrapperTest class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

namespace JustMisha\MultiRunner\Tests\Unit;

use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;
use JustMisha\MultiRunner\Tests\BaseTestCase;

/**
 * Tests methods of the OsWrapper class
 */
class OsWrapperTest extends BaseTestCase
{
    /**
     * Tests that OsCommandsWrapper::escapeArgWin32 can escape
     * a complicated argument for Windows.
     *
     * @return void
     */
    public function testEscapeComplicatedArgumentForWindows(): void
    {
        $osWrapper = new OsCommandsWrapper();
        $complicatedArgument = ' &()[]{}^=;!\'+,`~ "';
        $ecsapedArgument = '"' . str_replace('"', '\"', $complicatedArgument)  . '"';
        self::assertEquals($ecsapedArgument, $osWrapper->escapeArgWin32($complicatedArgument));
    }

    /**
     * Tests that OsCommandsWrapper::clearFolder can clear
     * the contents of a folder while leaving the folder untouched.
     *
     * @return void
     */
    public function testWeCanClearFolder(): void
    {
        $this->fillFolderWithTestContent($this->runtimeFullPath);

        (new OsCommandsWrapper())->clearFolder($this->runtimeFullPath);

        $this->assertFolderEmpty($this->runtimeFullPath);
    }

    /**
     * Fills the runtime folder ($this->runtimeFullPath) with
     * test folders and files.
     *
     * @param string $folder A full path to the folder to fill.
     * @return void
     */
    protected function fillFolderWithTestContent(string $folder): void
    {
        $dirFullPath = $folder . DIRECTORY_SEPARATOR . 'test1';
        if (!file_exists($dirFullPath)) {
            mkdir($dirFullPath, 0777, true);
        }
        file_put_contents($dirFullPath . DIRECTORY_SEPARATOR . 'test.txt', 'Hello');

        $dirFullPath = $folder . DIRECTORY_SEPARATOR . 'test2';
        if (!file_exists($dirFullPath)) {
            mkdir($dirFullPath, 0777, true);
        }
        file_put_contents($dirFullPath . DIRECTORY_SEPARATOR . 'test.txt', 'Hello');

        $dirFullPath = $folder . DIRECTORY_SEPARATOR . 'test3';
        if (!file_exists($dirFullPath)) {
            mkdir($dirFullPath, 0777, true);
        }
        file_put_contents($dirFullPath . DIRECTORY_SEPARATOR . 'test.txt', 'Hello');
    }
}
