<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\TheTabs;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// The platform lights the first item when no item says it is the one the
// screen is under, so every screen but the health ones would light Health.

/**
 * The ids of the items the drawn navigation marks as where this screen is.
 *
 * @return list<string>
 */
function itemsLitOn(?TheTabs $here): array
{
    $was = NativeTagPrecompiler::setActive(active: true);

    try {
        NativeElementCollector::reset();
        Blade::render(
            '<x-operator::screen-closes :goes="$goes" :here="$here" />',
            ['goes' => WhereAStackIs::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)))), 'here' => $here],
        );
        $tree = NativeElementCollector::collect();
    } finally {
        NativeTagPrecompiler::setActive(active: $was);
    }

    $id = 1;
    $emitted = [];
    $hashes = [];
    $drawn = $tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes);

    $items = data_get($drawn, 'children');
    $lit = [];

    if (! is_array($items)) {
        return $lit;
    }

    foreach ($items as $item) {
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
