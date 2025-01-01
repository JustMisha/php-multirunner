<?php

/**
 * MultiRunner test classes: BaseTestCase class.
 *
 * @package JustMisha\MultiRunner
 * @license https://github.com/JustMisha/php-multirunner/LICENSE.md MIT License
 */

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
     * The fake JustMisha\MultiRunner\file_put_contents function for testing.
     * Returns false if the global $mockFilePutContents is set to true.
     *
     * @param string $fileName The full path to the file where to put content.
     * @param string $fileContents The data to write.
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
     * the fake JustMisha\MultiRunner\mkdir function for testing.
     * Returns false if the global $mockMkdir is set to true.
     *
     * @param string $dir The name of the directory to create.
     * @param integer $rights The right set.
     * @param boolean $recursive If true, then any parent directories
     *                           to the directory specified will also
     *                           be created, with the same permissions.
     * @return boolean
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

    use FilesystemIterator;
    use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;

    /**
     * The base case class for all other MultiRunner tests.
     */
    class BaseTestCase extends \PHPUnit\Framework\TestCase
    {
        public const MAX_PARALLEL_PROCESSES = 100;

        public const TMP_DIR_NAME = 'tmp';

        /**
         * @var string The full path to the runtime directory.
         */
        protected string $runtimeFullPath;

        /**
         * @var OsCommandsWrapper The instance of the OsCommandWrapper.
         */
        protected OsCommandsWrapper $osCommandsWrapper;

        /**
         * The constructor of the class
         *
         * @param string|null $name
         * @param array $data
         * @param string $dataName
         * @param OsCommandsWrapper|null $osCommandsWrapper
         */
        public function __construct(
            ?string $name = null,
            array $data = [],
            string $dataName = '',
            OsCommandsWrapper $osCommandsWrapper = null
        ) {
            parent::__construct($name, $data, $dataName);
            if ($osCommandsWrapper) {
                $this->osCommandsWrapper = $osCommandsWrapper;
            } else {
                $this->osCommandsWrapper = new OsCommandsWrapper();
            }
            $this->runtimeFullPath = dirname(__FILE__, 1) . DIRECTORY_SEPARATOR . 'runtime';
        }

        /**
         * Empties the {@see $runtimeFullPath} and
         * the {@see self::TMP_DIR_NAME} directories before each test.
         *
         * @return void
         */
        protected function setUp(): void
        {
            $this->clearRuntimeFolder();
            $this->clearTmpFolder();
        }

        /**
         * Empties the {@see $runtimeFullPath} and
         * the {@see self::TMP_DIR_NAME} directories after each test.
         *
         * @return void
         */
        protected function tearDown(): void
        {
            $this->clearRuntimeFolder();
            $this->clearTmpFolder();
        }

        /**
         * Returns true if the code works on Windows.
         *
         * @return boolean
         */
        protected function isWindows(): bool
        {
            return $this->osCommandsWrapper->isWindows();
        }

        /**
         * Empties the {@see $runtimeFullPath} directory.
         *
         * @return void
         */
        protected function clearRuntimeFolder(): void
        {
            $this->osCommandsWrapper->clearFolder($this->runtimeFullPath);
        }

        /**
         * Empties the {@see self::TMP_DIR_NAME} directory.
         *
         * @return void
         */
        protected function clearTmpFolder(): void
        {
            $this->osCommandsWrapper->clearFolder(
                dirname(__FILE__, 1) .
                DIRECTORY_SEPARATOR .
                self::TMP_DIR_NAME
            );
        }

        /**
         * Asserts that a folder is empty.
         *
         * @param string $dir A full path to the folder.
         * @return void
         */
        protected function assertFolderEmpty(string $dir): void
        {
            $dirIterator = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
            $this->assertFalse($dirIterator->valid());
        }
    }
}
