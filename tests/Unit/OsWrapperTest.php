<?php

namespace JustMisha\MultiRunner\Tests\Unit;


use JustMisha\MultiRunner\Helpers\OsCommandsWrapper;
use JustMisha\MultiRunner\Tests\BaseTestCase;


class OsWrapperTest extends BaseTestCase
{
    public function testEscapeWhitespace(): void
    {
        $osWrapper = new OsCommandsWrapper();
        // &()[]{}^=;!'+,`~ "
        self::assertContains('"', str_split($osWrapper->quoteArgumentForWinCmd('bla bla')));

    }
}