<?php

declare(strict_types=1);

 /**
 * This allows us to configure the behavior of the "global mock"
 */
namespace {
    $mockFilePutContents = false;
    $mockMkdir = false;
}

 /**
 * Put mocks of global functions for testing in the namespace of the SUT
 */
namespace JustMisha\MultiRunner {

    /**
     * @param string $fileName
     * @param string $fileContents
     * @return mixed
     */
    function file_put_contents(string $fileName, string $fileContents)
    {
        global $mockFilePutContents;
        if (isset($mockFilePutContents) && $mockFilePutContents === true) {
            return false;
        } else {
            return call_user_func_array('\file_put_contents', func_get_args());
        }
    }

    /**
     * @param string $dir
     * @param int $rights
     * @param bool $recursive
     * @return bool
     */
    function mkdir(string $dir, int $rights, bool $recursive): bool
    {
        global $mockMkdir;
        if (isset($mockMkdir) && $mockMkdir === true) {
            return false;
        } else {
            return (bool)call_user_func_array('\mkdir', func_get_args());
        }
    }
}

namespace JustMisha\MultiRunner\Tests {

    use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

    class BaseTestCase extends \PHPUnit\Framework\TestCase
    {
        public const MAX_PARALLEL_PROCESSES = 100;

        protected string $runtimeFullPath;

        protected OsCommandsWrapper $osCommandsWrapper;

        public function __construct(
            ?string $name = null,
            array $data = [],
            $dataName = '',
            OsCommandsWrapper $osCommandsWrapper = null
        )
        {
            parent::__construct($name, $data, $dataName);
            if ($osCommandsWrapper) {
                $this->osCommandsWrapper = $osCommandsWrapper;
            } else {
                $this->osCommandsWrapper = new OsCommandsWrapper();
            }
            $this->runtimeFullPath = dirname(__FILE__, 1) . DIRECTORY_SEPARATOR . 'runtime';
        }

        protected function isWindows(): bool
        {
            return $this->osCommandsWrapper->isWindows();
        }

        protected function clearRuntimeFolder(): void
        {
            $this->osCommandsWrapper->clearFolder($this->runtimeFullPath);
        }

        /**
         * Assert that a folder is empty
         *
         * @param string $dir A full path to the folder.
         * @return void
         */
        protected function assertFolderEmpty(string $dir): void
        {
            $dirIterator = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $this->assertFalse($dirIterator->valid());
        }
    }
}