<?php

declare(strict_types=1);

use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\TheWaysAround;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatTheRouterHolds;

// A screen without a stack has no menu, so its top bar is where the way back
// is: drawn wherever a screen lies beneath it, and nowhere else.

/**
 * What the top bar of a screen without a stack says, opened over so many screens.
 *
 * The bar is the screen's native stack root; a screen that draws none, as the
 * lock does, answers nothing. Named for this file.
 *
 * @return array<mixed>|null
 */
function theBarOf(AScreenWithoutAStack $where, int $beneathIt): ?array
{
    $screen = theScreenAt($where, $beneathIt);

    return $screen instanceof NativeComponent ? theStackRootIn(WhatTheDeviceWouldDraw::tree($screen)) : null;
}

/** The screen a path without a stack opens, opened over so many screens, or none. */
function theScreenAt(AScreenWithoutAStack $where, int $beneathIt): ?NativeComponent
{
    $resolved = NativeRouter::resolve($where->value);
    $class = is_array($resolved) && is_string($resolved['class'] ?? null) ? $resolved['class'] : '';
    $screen = app()->make($class);

    if (! $screen instanceof NativeComponent) {
        return null;
    }

    WhatTheRouterHolds::over($screen, $where->value, ...array_fill(0, $beneathIt, AScreenWithoutAStack::TheList->value));

    return $screen;
}

/**
 * Whether a drawn tree holds a node of one type anywhere in it.
 *
 * @param array<mixed> $node
 */
function holdsANodeOf(array $node, string $type): bool
{
    if (($node['type'] ?? null) === $type) {
        return true;
    }

    return array_any($node, static fn(mixed $child): bool => is_array($child) && holdsANodeOf($child, $type));
}

/**
 * The first native stack root in a drawn tree, by its props.
 *
 * @return array<mixed>|null
 */
function theStackRootIn(mixed $node): ?array
{
    if (! is_array($node)) {
        return null;
    }

    if (($node['type'] ?? null) === 'native_root_stack' && is_array($node['props'] ?? null)) {
        return $node['props'];
    }

    foreach ($node as $child) {
        $found = theStackRootIn($child);

        if ($found !== null) {
            return $found;
        }
    }

    return null;
}

it('offers the way back on every screen without a stack that was opened over another', function (): void {
    $without = [];
    $drawn = 0;

    foreach (AScreenWithoutAStack::cases() as $where) {
        $bar = theBarOf($where, beneathIt: 1);

        if ($bar === null) {
            continue;
        }

        $drawn++;

        if (($bar['back'] ?? false) !== true) {
            $without[] = $where->name;
        }
    }

    expect($drawn)->toBeGreaterThan(3, 'no screen without a stack drew a top bar, so nothing was looked at')
        ->and($without)->toBe([], 'These were opened over another screen and offer no way back.');
});

it('offers no way back on a screen at the bottom of the stack', function (): void {
    $with = [];

    foreach (AScreenWithoutAStack::cases() as $where) {
        if ((theBarOf($where, beneathIt: 0)['back'] ?? false) === true) {
            $with[] = $where->name;
        }
    }

    expect($with)->toBe([], 'These have nothing beneath them and offer a way back all the same.');
});

it('draws no menu, no list of stacks and no bar on a screen without a stack', function (): void {
    $drawing = [];
    $looked = 0;

    foreach (AScreenWithoutAStack::cases() as $where) {
        $screen = theScreenAt($where, beneathIt: 0);

        if (! $screen instanceof NativeComponent) {
            continue;
        }

        $looked++;
        $tree = WhatTheDeviceWouldDraw::tree($screen);

        if (method_exists($screen, 'drawerOverride') && $screen->drawerOverride() !== null) {
            $drawing[] = sprintf('%s draws the menu', $where->name);
        }

        if (TheWaysAround::theListOfStacksIn($tree) !== []) {
            $drawing[] = sprintf('%s draws the list of stacks', $where->name);
        }

        if (holdsANodeOf($tree, 'bottom_nav_item')) {
            $drawing[] = sprintf('%s draws the bar', $where->name);
        }
    }

    expect($looked)->toBe(count(AScreenWithoutAStack::cases()), 'a screen without a stack was not built, so it was not looked at')
        ->and($drawing)->toBe([]);
});
