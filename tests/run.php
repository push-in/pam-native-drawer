<?php

declare(strict_types=1);

$core = getenv('PAM_NATIVE_CORE') ?: dirname(__DIR__).'/vendor/pushinbr/pam-native';
$runner = $core.'/packages/native/tests/run.php';
if (!is_file($runner)) {
    fwrite(STDERR, "Set PAM_NATIVE_CORE to a PAM Native source checkout.\n");
    exit(1);
}
$roots = array_filter(explode(PATH_SEPARATOR, getenv('PAM_TEST_PLUGIN_ROOTS') ?: ''));
$roots[] = dirname(__DIR__);
putenv('PAM_TEST_PLUGIN_ROOTS='.implode(PATH_SEPARATOR, array_unique($roots)));
require $runner;
