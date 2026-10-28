<?php

declare(strict_types=1);

namespace Pam\Native\Drawer;

use BackedEnum;
use Closure;
use LogicException;
use Pam\Native\Renderable;
use Pam\Native\Routing\Route;
use Pam\Native\Routing\RouteName;

/** Declarative route registration owned by the optional drawer plugin. */
final class DrawerRoutes
{
    private static ?DrawerRegistrar $registrar = null;

    /** @param null|Closure(DrawerRouter): DrawerRouter $configure */
    public static function make(string $name, string|BackedEnum $initial, Closure $routes, ?Closure $configure = null): DrawerNavigator
    {
        return Route::customNavigator(static function () use ($name, $initial, $routes, $configure): DrawerNavigator {
            $parent = self::$registrar;
            $registrar = new DrawerRegistrar($name, RouteName::value($initial));
            self::$registrar = $registrar;
            try {
                $routes();
            } finally {
                self::$registrar = $parent;
            }
            return $registrar->build($configure);
        });
    }

    /** @param class-string<Renderable>|Renderable|Closure $screen */
    public static function screen(string|BackedEnum $name, string|Renderable|Closure $screen, string $label, ?Renderable $icon = null, ?string $badge = null, ?string $group = null): void
    {
        $registrar = self::$registrar
            ?? throw new LogicException('DrawerRoutes::screen() must be declared inside DrawerRoutes::make().');
        $registrar->add(RouteName::value($name), $label, Route::screenContent($name, $screen), $icon, $badge, $group);
    }
}
