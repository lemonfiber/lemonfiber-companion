<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The chrome, asked of a rendered frame rather than of a template's text.
//
// Every other suite that touches the chrome reads the Blade — that the bars are
// top-level siblings, that each control carries a label, that no tag resolves to
// nothing. All of it is true of a template that draws none of it: a component
// whose content arrives as slot content is collected before the component's own
// template runs, which renders the padded column empty and last and passes every
// text rule there is.
//
// So this renders. What it asserts is the two things the text cannot show —
// that the chrome reaches the frame at all, and that an obstacle *replaces* the
// reading rather than sitting above it.

/** The moment a frame is read at. Named for this file (`G10`). */
const WHEN_THE_CHROME_WAS_DRAWN = 1_770_000_000;

/** The machine every frame here is about. */
function theMachineOnTheFrame(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

it('the bars reach a stack-scoped frame, and the reading is replaced rather than buried', function (): void {
    $stack = theMachineOnTheFrame();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        AStackThatSpeaksUp::holdingOpen(),
        FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)),
        ACaptureInMemory::inFront(),
        StandingsInMemory::working(),
        WhatThePhoneKeeps::nothingYet(),
        new AppsSettingsThatOpen(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    // What stood between this frame and the machine, and
    // what to do about it — both, because what happened is a fact about the
    // world and what to do about it is advice.
    expect($drawn->said())->toContain(__(KindOfObstacle::DeviceHasNoNetwork->said()))
        ->and($drawn->said())->toContain(__(KindOfObstacle::DeviceHasNoNetwork->remedy()))
        // The four readings of this machine, drawn by the platform's own bar.
        // Asserted by their labels because that is what somebody sees and what
        // a screen reader says; the icons and the routes are the bar's own.
        ->and($drawn->said())->toContain(__('navigation.health'))
        ->and($drawn->said())->toContain(__('navigation.services'))
        ->and($drawn->said())->toContain(__('navigation.updates'))
        ->and($drawn->said())->toContain(__('navigation.repairs'))
        // The way forward is kept: an obstacle never takes the action
        // away, so the frame that says the network is down still offers the
        // retry.
        ->and($drawn->offers())->toBe([__('health.ask_again')]);
});

it('the word a frame opens on is drawn, with its age', function (): void {
    $stack = theMachineOnTheFrame();
    $stacks = StacksInMemory::holding($stack);
    $standings = StandingsInMemory::working()->lastHeard(
        $stack->id(),
        HowItStands::Broken,
        Instant::atEpochSeconds(WHEN_THE_CHROME_WAS_DRAWN - 10),
    );

    $screen = new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $standings,
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_CHROME_WAS_DRAWN)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );

    $drawn = WhatTheDeviceWouldDraw::by($screen);
    // The row says how the machine stands on the one line under its name.
    $lines = implode("\n", $drawn->said());

    // The word and the machine it is about, on the first frame, without a
    // network round trip — which is the whole of what the opening asks for and what
    // a retained reading permits: it came out of a store, so it is retained, so it
    // carries when it was read.
    // The machine is a row that is tapped, so its name is what the frame
    // offers — and a reader hears the name the row answers to, which is why
    // that is asserted as well as the row's own words.
    $word = __(HowItStands::Broken->saidInAWord());

    expect($lines)->toContain(sprintf('%s · %s', is_string($word) ? $word : '', trans_choice('health.ago.minutes', 0)))
        ->and($drawn->offers())->toContain($stack->name()->shown())
        ->and(whatAReaderHearsOn(WhatTheDeviceWouldDraw::tree($screen)))->toContain(__('connection.open_stack', ['stack' => $stack->name()->shown()]));
});

/**
 * Every name a screen reader is given on a frame, in draw order.
 *
 * @param array<array-key, mixed> $tree
 *
 * @return list<string>
 */
function whatAReaderHearsOn(array $tree): array
{
    $named = [];

    array_walk_recursive($tree, static function (mixed $value, int|string $prop) use (&$named): void {
        if ($prop === 'a11y_label' && is_string($value)) {
            $named[] = $value;
        }
    });

    return $named;
}
