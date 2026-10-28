<?php

declare(strict_types=1);

namespace Pam\Native\Drawer;

use InvalidArgumentException;
use Pam\Native\Plugin\PluginProvider;
use Pam\Native\TemplateRegistry;

final class DrawerPluginProvider implements PluginProvider
{
    public function register(): void
    {
        TemplateRegistry::component('DrawerLayoutAndroid', static function (array $props, array $children): DrawerLayoutAndroid {
            if (count($children) !== 2) {
                throw new InvalidArgumentException('DrawerLayoutAndroid requires content and drawer children.');
            }
            return DrawerLayoutAndroid::make($children[0], $children[1]);
        });
    }

    public function boot(): void {}
}
