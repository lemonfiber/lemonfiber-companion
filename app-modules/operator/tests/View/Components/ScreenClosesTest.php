<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Tests\TestCase;

// The application is booted here, unlike most component tests: a render needs
// the view factory, the component namespace and the precompiler.
uses(TestCase::class);

// The platform lights the first item when no item says it is the one the
// screen belongs to, so every screen but the health ones would light Health.

/**
 * The ids of the items the drawn navigation marks as where this screen is.
 *
 * @return list<string>
 */
function itemsLitOn(string $here): array
{
    $was = NativeTagPrecompiler::setActive(active: true);

    try {
        NativeElementCollector::reset();
        Blade::render(
            sprintf('<x-operator::screen-closes :goes="$goes" here="%s" />', $here),
            ['goes' => WhereAStackIs::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))))],
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

it('lights the item of the section the screen belongs to, and only that one', function (string $here): void {
    expect(itemsLitOn($here))->toBe([$here]);
})->with(['health', 'services', 'updates', 'repairs']);

it('has every screen name a section the navigation has', function (): void {
    $views = glob(sprintf('%s/app-modules/*/resources/views/*.blade.php', base_path()));
    $named = [];

    foreach (is_array($views) ? $views : [] as $view) {
        $markup = (string) file_get_contents($view);

        if (! str_contains($markup, '<x-operator::screen-closes')) {
            continue;
        }

        $found = preg_match('/<x-operator::screen-closes[^\n]*?\bhere="([a-z]+)"/', $markup, $here) === 1;
        $named[basename($view)] = $found ? $here[1] : '';
    }

    expect($named)->not->toBeEmpty()
        ->and(array_filter($named, static fn(string $here): bool => ! in_array($here, ['health', 'services', 'updates', 'repairs'], strict: true)))->toBe([]);
});
