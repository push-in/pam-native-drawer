<?php

declare(strict_types=1);

use Pam\Native\App;
use Pam\Native\Component;
use Pam\Native\Renderable;
use Pam\Native\EventKind;
use Pam\Native\PropKey;
use Pam\Native\WindowMetrics;
use Pam\Native\Internal\Runtime;
use Pam\Native\Internal\Wire;
use Pam\Native\Routing\Route;
use Pam\Native\Navigation\Router;
use Pam\Native\UI\Screen;
use Pam\Native\UI\Text;
use Pam\Native\Routing\Navigation as NamedNavigation;

// Every legacy class and enum preserves identity when installed.
foreach ([
    'Pam\Native\Routing\DrawerRegistrar' => 'Pam\Native\Drawer\DrawerRegistrar',
    'Pam\Native\UI\DrawerLayoutAndroid' => 'Pam\Native\Drawer\DrawerLayoutAndroid',
    'Pam\Native\Navigation\DrawerPosition' => 'Pam\Native\Drawer\DrawerPosition',
    'Pam\Native\Navigation\DrawerBackBehavior' => 'Pam\Native\Drawer\DrawerBackBehavior',
    'Pam\Native\Navigation\NavigationDrawerItem' => 'Pam\Native\Drawer\NavigationDrawerItem',
    'Pam\Native\Navigation\DrawerStatusBarAnimation' => 'Pam\Native\Drawer\DrawerStatusBarAnimation',
    'Pam\Native\Navigation\DrawerNavigator' => 'Pam\Native\Drawer\DrawerNavigator',
    'Pam\Native\Navigation\DrawerRouter' => 'Pam\Native\Drawer\DrawerRouter',
    'Pam\Native\Navigation\DrawerKeyboardDismissMode' => 'Pam\Native\Drawer\DrawerKeyboardDismissMode',
    'Pam\Native\Navigation\DrawerType' => 'Pam\Native\Drawer\DrawerType',
 ] as $legacy => $target) {
    $assert(class_exists($legacy) && is_a($legacy, $target, true), 'Legacy alias identity: '.$legacy);
}

$namedDrawer = Route::drawer(
    'named-drawer-test',
    'inbox',
    static function (): void {
        Route::drawerScreen('inbox', Screen::make(Text::make('Inbox')), label: 'Inbox', badge: '3');
        Route::drawerScreen('archive', Screen::make(Text::make('Archive')), label: 'Archive', group: 'Library');
    },
    static fn (\Pam\Native\Navigation\DrawerRouter $drawer): \Pam\Native\Navigation\DrawerRouter =>
        $drawer->responsive(720.0)->gestures(edgeWidth: 40.0),
);
$assert(
    NamedNavigation::navigate('archive')
        && $namedDrawer->selectedRoute() === 'archive',
    'Laravel-style drawers must expose grouped destinations and the complete router configurator.',
);
$groupedDrawer = Router::drawer('overview')
    ->route('overview', 'Overview', Screen::make(Text::make('Overview')))
    ->route(
        'button',
        'Button',
        Screen::make(Text::make('Button')),
        group: 'Actions',
    )
    ->route(
        'field',
        'Text field',
        Screen::make(Text::make('Text field')),
        group: 'Forms',
    )
    ->persistence('test-grouped-drawer')
    ->build();
$assert(
    !$groupedDrawer->isGroupExpanded('Actions')
        && $groupedDrawer->toggleGroup('Actions')
        && $groupedDrawer->isGroupExpanded('Actions')
        && $groupedDrawer->navigate('field')
        && $groupedDrawer->isGroupExpanded('Forms'),
    'Grouped drawer routes must collapse cleanly and keep every destination navigable.',
);
$restoredGroupedDrawer = Router::drawer('overview')
    ->route('overview', 'Overview', Screen::make(Text::make('Overview')))
    ->route(
        'button',
        'Button',
        Screen::make(Text::make('Button')),
        group: 'Actions',
    )
    ->route(
        'field',
        'Text field',
        Screen::make(Text::make('Text field')),
        group: 'Forms',
    )
    ->persistence('test-grouped-drawer')
    ->build();
$assert(
    $restoredGroupedDrawer->selectedRoute() === 'field'
        && $restoredGroupedDrawer->isGroupExpanded('Actions')
        && $restoredGroupedDrawer->isGroupExpanded('Forms'),
    'Grouped drawer state must restore selection and expanded sections.',
);

$responsiveMetricsReader = new class extends Component {
    public int $renders = 0;

    protected function initialState(): array
    {
        return ['revision' => 0];
    }

    public function render(): Renderable
    {
        $this->renders++;
        $this->state->revision;
        App::windowMetrics();

        return Text::make('Nested responsive navigator metrics');
    }
};
App::run($responsiveMetricsReader);
Runtime::dispatchEvent(0, EventKind::Dimensions->value, Wire::map([
    'width' => 1_000.0,
    'height' => 600.0,
    'density' => 2.0,
]));
$nestedResponsiveDrawer = Router::drawer('overview')
    ->route('overview', 'Overview', Screen::make(Text::make('Overview')))
    ->route('field', 'Text field', Screen::make(Text::make('Text field')))
    ->responsive(840.0)
    ->persistence('test-nested-responsive-drawer-presentation')
    ->build();
$assert(
    $responsiveMetricsReader->renders === 2
        && $nestedResponsiveDrawer->resolvedType()
        === \Pam\Native\Navigation\DrawerType::Permanent,
    'Window-metric readers and nested responsive drawers must update without being the App root.',
);
Runtime::shutdown();

$responsiveDrawer = Router::drawer('overview')
    ->route('overview', 'Overview', Screen::make(Text::make('Overview')))
    ->route('field', 'Text field', Screen::make(Text::make('Text field')))
    ->responsive(840.0)
    ->persistence('test-responsive-drawer-presentation')
    ->build();
$responsiveDrawer->dimensions(new WindowMetrics(1_000.0, 600.0, 2.0));
$permanentDrawerElement = $responsiveDrawer->toElement();
$permanentOpen = $permanentDrawerElement->events()[EventKind::DrawerOpen->value] ?? null;
$permanentOpen?->__invoke();
$responsiveDrawer->dimensions(new WindowMetrics(412.0, 915.0, 2.0));
$compactDrawerElement = $responsiveDrawer->toElement();
$assert(
    $responsiveDrawer->resolvedType() === \Pam\Native\Navigation\DrawerType::Front
        && $responsiveDrawer->getState()['open'] === false
        && $compactDrawerElement->properties()[PropKey::DrawerOpen->value] === false,
    'Permanent drawer callbacks must not leak an open modal drawer into the compact layout after rotation.',
);
