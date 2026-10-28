<?php

/*
 * Run from a Thelia install:
 * vendor/bin/phpunit --bootstrap vendor/thelia/modules/ColissimoLabel/tests/bootstrap.php vendor/thelia/modules/ColissimoLabel/tests
 */

require_once getcwd().'/vendor/autoload.php';

// Thelia loads the module classes when the kernel boots; these tests run without it.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'ColissimoLabel\\')) {
        $file = \dirname(__DIR__).'/'.str_replace('\\', '/', substr($class, \strlen('ColissimoLabel\\'))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
