<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class OsWrapperTest extends BaseTestCase
{
    public function testEscapeComplicatedArgument(): void
    {
        $osWrapper = new OsCommandsWrapper();
        $complicatedArgument = ' &()[]{}^=;!\'+,`~ "';
        self::assertContains('"', str_split($osWrapper->escapeArgWin32($complicatedArgument)));

    }
}