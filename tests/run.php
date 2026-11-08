<?php

declare(strict_types=1);

// Runs the plugin contracts against the PAM Native that Composer installed
// (or the autoloader named by PAM_TEST_AUTOLOAD, e.g. a core checkout).
require __DIR__.'/bootstrap.php';
$manifest = json_decode((string) file_get_contents(dirname(__DIR__).'/pam-native.plugin.json'), true, flags: JSON_THROW_ON_ERROR);
$providerClass = $manifest['php']['provider'];
$provider = new $providerClass();
$provider->register();
$provider->boot();
require __DIR__.'/core-compat.php';
echo "{$assertions} assertions, 0 failures\n";
