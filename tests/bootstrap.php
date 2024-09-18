<?php

$autoloadPhp = __DIR__ . "/../vendor/autoload.php";

if (!file_exists($autoloadPhp)) {
    die(
        "\n[ERROR] You need to run composer before running the test suite.\n".
        "To do so run the following commands:\n".
        "    curl -s http://getcomposer.org/installer | php\n".
        "    php composer.phar install\n\n"
    );
}

require_once $autoloadPhp;
