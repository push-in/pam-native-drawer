<?php

declare(strict_types=1);

$autoload = getenv('PAM_TEST_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
require $autoload;

// Persisted navigator state must never leak between runs or into the
// developer's own state directory.
$stateDirectory = sys_get_temp_dir().'/pam-native-plugin-tests-'.getmypid();
putenv('PAM_NATIVE_STATE_DIR='.$stateDirectory);
// Registered from a shutdown function so it runs after the runtime's own
// shutdown flush of the state file.
register_shutdown_function(static function () use ($stateDirectory): void {
    register_shutdown_function(static function () use ($stateDirectory): void {
        foreach (glob($stateDirectory.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) unlink($file);
        }
        if (is_dir($stateDirectory)) rmdir($stateDirectory);
    });
});

final class TestDiagnostics
{
    public static array $messages = [];
    public static ?array $moduleCall = null;
    public static ?array $typedCall = null;
}

if (!function_exists('pam_native_error')) {
    function pam_native_error(string $message): void { TestDiagnostics::$messages[] = $message; }
}
if (!function_exists('pam_native_call')) {
    function pam_native_call(int $requestId, string $module, string $method, string $payload): void
    {
        TestDiagnostics::$moduleCall = compact('requestId', 'module', 'method', 'payload');
    }
}
if (!function_exists('pam_native_call_typed')) {
    function pam_native_call_typed(int $requestId, int $operation, string $payload): void
    {
        TestDiagnostics::$typedCall = compact('requestId', 'operation', 'payload');
    }
}

$assertions = 0;
$assert = static function (bool $condition, string $message) use (&$assertions): void {
    ++$assertions;
    if (!$condition) throw new RuntimeException($message);
};
