<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\ViewModels\AMarkAsShown;
use Modules\Operator\Internal\ViewModels\TheTabsAsMarked;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Wayfinding\Api\TheTabs;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Platform;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// The platform lights the first item when no item says it is the one the
// screen is under, so every screen but the health ones would light Health.

/**
 * The items of the drawn navigation, as the platform receives them.
 *
 * @return list<mixed>
 */
function itemsDrawnOn(?TheTabs $here, TheTabsAsMarked $marks = new TheTabsAsMarked()): array
{
    $was = NativeTagPrecompiler::setActive(active: true);

    try {
        NativeElementCollector::reset();
        Blade::render(
            '<x-operator::screen-closes :goes="$goes" :here="$here" :marks="$marks" />',
            ['goes' => WhereAStackIs::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)))), 'here' => $here, 'marks' => $marks],
        );
        $tree = NativeElementCollector::collect();
    } finally {
        NativeTagPrecompiler::setActive(active: $was);
    }

    $id = 1;
    $emitted = [];
    $hashes = [];
    $items = data_get($tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes), 'children');

    return is_array($items) ? array_values($items) : [];
}

/**
 * The ids of the items the drawn navigation marks as where this screen is.
 *
 * @return list<string>
 */
function itemsLitOn(?TheTabs $here): array
{
    $lit = [];

    foreach (itemsDrawnOn($here) as $item) {
        $id = data_get($item, 'props.id');

        if (data_get($item, 'props.active') === true && is_string($id)) {
            $lit[] = $id;
        }
    }

    return $lit;
}

it('lights the item of the tab the screen is under, and only that one', function (TheTabs $here): void {
    expect(itemsLitOn($here))->toBe([$here->value]);
})->with(TheTabs::cases());

it('lights nothing for a screen no tab owns', function (): void {
    expect(itemsLitOn(null))->toBe([]);
});

it('has every screen say its tab through the screen, rather than by a name written in the view', function (): void {
    $views = glob(sprintf('%s/app-modules/*/resources/views/*.blade.php', base_path()));
    $named = [];

    foreach (is_array($views) ? $views : [] as $view) {
        $markup = (string) file_get_contents($view);

        if (! str_contains($markup, '<x-operator::screen-closes')) {
            continue;
        }

        $named[basename($view)] = str_contains($markup, ':here="$this->itsTab()"');
    }

    expect($named)->not->toBeEmpty()
        ->and(array_keys(array_filter($named, static fn(bool $says): bool => ! $says)))->toBe([]);
});

/**
 * Each item's label and icon, as the bar is drawn on one platform.
 *
 * @return list<array{mixed, mixed}>
 */
function itemsAsDrawnOn(string $platform, TheTabsAsMarked $marks): array
{
    Platform::set($platform);

    try {
        return array_map(static fn(mixed $item): array => [data_get($item, 'props.label'), data_get($item, 'props.icon')], itemsDrawnOn(null, $marks));
    } finally {
        Platform::set(null);
    }
}

it('draws every tab with its own label and its icon on each platform, marked or not', function (TheTabsAsMarked $marks): void {
    expect(itemsAsDrawnOn(Platform::ANDROID, $marks))->toBe(array_map(static fn(TheTabs $tab): array => [__($tab->said()), $tab->glyph()], TheTabs::cases()))
        ->and(itemsAsDrawnOn(Platform::IOS, $marks))->toBe(array_map(static fn(TheTabs $tab): array => [__($tab->said()), $tab->iosGlyph()], TheTabs::cases()));
})->with([
    'nothing new' => [new TheTabsAsMarked()],
    'something new on Health and Updates' => [new TheTabsAsMarked(new AMarkAsShown(2, '2'), new AMarkAsShown(1, '1'))],
]);
