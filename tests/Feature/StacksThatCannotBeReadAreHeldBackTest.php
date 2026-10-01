<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhyTheStacksAreHeldBack;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// Stacks still on the phone that a launch cannot read are held back.
//
// Read as a phone holding nothing, the launch would open on the first run and
// end on a pairing, and a pairing writes a new record over the one still
// holding them. So the screen says why they cannot be listed and what to do,
// and offers nothing that would write.

/** The moment the launch is read at. Named for this file (`G10`). */
const WHEN_THE_STACKS_WERE_HELD_BACK = 1_770_000_000;

/** The launch screen over a phone whose record is held back for this reason. */
function theScreenOverStacksHeldBack(WhyTheStacksAreHeldBack $why): YourStacks
{
    $stacks = StacksInMemory::heldBack($why);

    return new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        StandingsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_STACKS_WERE_HELD_BACK)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );
}

it('says why the stacks are held back, and what to do about it', function (WhyTheStacksAreHeldBack $why): void {
    $drawn = WhatTheDeviceWouldDraw::by(theScreenOverStacksHeldBack($why));

    expect($drawn->said())->toContain(__($why->said()))
        ->and($drawn->said())->toContain(__($why->remedy()));
})->with(WhyTheStacksAreHeldBack::cases());

it('opens on no first run and offers no pairing over stacks held back', function (WhyTheStacksAreHeldBack $why): void {
    $screen = theScreenOverStacksHeldBack($why);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->nothingIsPairedYet())->toBeFalse()
        ->and($screen->pairingIsOffered())->toBeFalse()
        ->and($drawn->said())->not->toContain(__('onboarding.what_this_is'))
        ->and($drawn->offers())->not->toContain(__('connection.pair'));
})->with(WhyTheStacksAreHeldBack::cases());

/**
 * The elements directly inside one, in order. Named for this file (`G10`).
 *
 * @param array<array-key, mixed> $node
 *
 * @return list<array<array-key, mixed>>
 */
function theElementsIn(array $node): array
{
    $children = array_key_exists('children', $node) && is_array($node['children']) ? $node['children'] : [];

    return array_values(array_filter($children, is_array(...)));
}

/**
 * Every element in a tree, the tree's own first. Named for this file (`G10`).
 *
 * @param array<array-key, mixed> $node
 *
 * @return list<array<array-key, mixed>>
 */
function everyElementIn(array $node): array
{
    $found = [$node];

    foreach (theElementsIn($node) as $child) {
        $found = [...$found, ...everyElementIn($child)];
    }

    return $found;
}

/**
 * What the frame's scrolling content draws, one entry per element directly in it.
 *
 * @param array<array-key, mixed> $tree
 *
 * @return list<array<array-key, mixed>>
 */
function whatTheContentHolds(array $tree): array
{
    $scrolling = array_values(array_filter(
        everyElementIn($tree),
        static fn(array $node): bool => array_key_exists('type', $node) && $node['type'] === 'scroll_view',
    ));
    $content = $scrolling === [] ? [] : theElementsIn($scrolling[0]);

    return $content === [] ? [] : theElementsIn($content[0]);
}

it('draws the notice and a report for someone helping, and no list under them', function (): void {
    $screen = theScreenOverStacksHeldBack(WhyTheStacksAreHeldBack::TheStoreWouldNotOpen);

    // An empty list under the notice would read as a phone holding nothing.
    expect(whatTheContentHolds(WhatTheDeviceWouldDraw::tree($screen)))->toHaveCount(2)
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('device.share_diagnostics'));
});
