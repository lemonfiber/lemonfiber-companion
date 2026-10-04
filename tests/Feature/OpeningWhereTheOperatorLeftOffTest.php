<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheOperatorIsHere;
use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhichTab;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Api\NotingWhereTheOperatorIs;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Modules\Operator\Internal\Screens\YourStacks;
use Modules\Operator\Internal\WhereAStackOpens;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;
use Tests\Support\WhatThePhoneKeeps;

// The app opens where the operator left off, and a chosen stack on its last tab.

/** When the opening happens. */
const WHEN_THE_APP_OPENS = 1_790_000_000;

/** A stack on the phone, named for this file. */
function aStackLeftOff(string $called, string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** A phone signed in as the operator to every stack named. */
function signedInTo(Stack ...$stacks): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();

    foreach ($stacks as $stack) {
        $keychain->keep($stack->id(), Session::of('a-session'), Whose::theOperator());
    }

    return $keychain;
}

/** The list as the app opens on it, over these stacks and where the operator was. */
function theListTheAppOpensOn(StacksInMemory $stacks, WhereTheOperatorWasInMemory $was, ?AKeychainInMemory $keychain = null, bool $clears = false): YourStacks
{
    $keychain ??= signedInTo(...iterator_to_array($stacks->configured(), preserve_keys: false));

    return new YourStacks(
        $stacks,
        $keychain,
        AShareSheetThatWasOffered::working(),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        $clears ? WhatThePhoneKeeps::clearedAtOpening() : WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingTooOld(),
        WhatThePhoneKeeps::nothingToFinish(),
        new WhereAStackOpens(AroundThePhone::holding($stacks, storage: $keychain, was: $was)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(clock: FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_APP_OPENS))),
    );
}

/** Where a screen sent the operator, or nowhere. */
function whereTheOpeningWent(NativeComponent $screen): string
{
    return $screen->getNavigationIntent()->uri ?? '';
}

it('opens on the stack the operator was last on, on the tab they last used there', function (): void {
    $loft = aStackLeftOff('The loft', 'a');
    $attic = aStackLeftOff('The attic', 'b');
    $was = WhereTheOperatorWasInMemory::nowhere();
    $was->wasOn($attic->id(), WhichTab::Updates);

    $list = theListTheAppOpensOn(StacksInMemory::holding($loft, $attic), $was);
    $list->mount();

    expect(whereTheOpeningWent($list))->toBe(AStacksScreen::Updates->forTheStack($attic->id()));
});

it('opens on the first stack in the order where the one last used is gone, on its last tab', function (): void {
    $loft = aStackLeftOff('The loft', 'a');
    $attic = aStackLeftOff('The attic', 'b');
    $was = WhereTheOperatorWasInMemory::nowhere();
    $was->wasOn($attic->id(), WhichTab::Repairs);
    $was->wasOn(aStackLeftOff('Gone', 'c')->id(), WhichTab::Updates);
    $stacks = StacksInMemory::holding($loft, $attic);
    $stacks->putInOrder($attic->id(), $loft->id());

    $list = theListTheAppOpensOn($stacks, $was);
    $list->mount();

    expect(whereTheOpeningWent($list))->toBe(AStacksScreen::Repairs->forTheStack($attic->id()));
});

it('opens a stack it holds no session for on its sign-in', function (): void {
    $loft = aStackLeftOff('The loft', 'a');

    $list = theListTheAppOpensOn(StacksInMemory::holding($loft), WhereTheOperatorWasInMemory::nowhere(), AKeychainInMemory::working());
    $list->mount();

    expect(whereTheOpeningWent($list))->toBe(AStacksScreen::SignIn->forTheStack($loft->id()));
});

it('stays on the list where no stack is held, and where the opening cleared what was kept', function (bool $holdsAStack, bool $clears): void {
    $stacks = $holdsAStack ? StacksInMemory::holding(aStackLeftOff('The loft', 'a')) : StacksInMemory::working();
    $list = theListTheAppOpensOn($stacks, WhereTheOperatorWasInMemory::nowhere(), clears: $clears);
    $list->mount();

    expect(whereTheOpeningWent($list))->toBe('');
})->with([
    'no stack held' => [false, false],
    'the opening cleared what was kept' => [true, true],
]);

it('stays on the list the second time it is built in a run', function (): void {
    $stacks = StacksInMemory::holding(aStackLeftOff('The loft', 'a'));
    $landing = new WhereAStackOpens(AroundThePhone::holding($stacks, storage: signedInTo(aStackLeftOff('The loft', 'a'))));

    expect($landing->theOpening())->not->toBe('')
        ->and($landing->theOpening())->toBe('');
});

it('opens a stack chosen from the list on the tab last used there', function (): void {
    $attic = aStackLeftOff('The attic', 'b');
    $barn = aStackLeftOff('The barn', 'c');
    $keychain = signedInTo($attic, $barn);
    $was = WhereTheOperatorWasInMemory::nowhere();
    $was->wasOn($barn->id(), WhichTab::Services);
    $screen = new HowCurrentThisStackIs(
        AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($attic, $barn), storage: $keychain, was: $was),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        WhatThePhoneKeeps::noUpkeepYet(),
    );
    $screen->setParams(['stack' => $attic->id()->stored()]);

    $screen->openTheStack($barn->id()->stored());

    expect(whereTheOpeningWent($screen))->toBe(AStacksScreen::Services->forTheStack($barn->id()));
});

it('notes a screen under a tab as where the operator is, and nothing for a screen the menu opens', function (): void {
    $attic = aStackLeftOff('The attic', 'b');
    $was = WhereTheOperatorWasInMemory::nowhere();
    $noting = new NotingWhereTheOperatorIs($was);
    $updates = new HowCurrentThisStackIs(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($attic)), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noUpkeepYet());
    $updates->setParams(['stack' => $attic->id()->stored()]);
    $words = new WhatTheWordsMean(AStackThatExplainsItsWords::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($attic)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $words->setParams(['stack' => $attic->id()->stored()]);
    $nameless = new HowCurrentThisStackIs(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($attic)), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noUpkeepYet());
    $nameless->setParams(['stack' => ' ']);

    expect($noting->cameToTheFront($updates))->toBeTrue()
        ->and($noting->cameToTheFront($words))->toBeFalse()
        ->and($noting->cameToTheFront($nameless))->toBeFalse()
        ->and($was->wasLastOn($attic->id()))->toBeTrue()
        ->and($was->tabOf($attic->id()))->toBe(WhichTab::Updates);
});

it('hands the operator each screen that comes to the front once, however many frames it publishes', function (): void {
    $attic = aStackLeftOff('The attic', 'b');
    $was = WhereTheOperatorWasInMemory::nowhere();
    $observer = new TheOperatorIsHere(new NotingWhereTheOperatorIs($was));
    $updates = new HowCurrentThisStackIs(AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)), AKeychainInMemory::working(), AroundThePhone::holding(StacksInMemory::holding($attic)), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noUpkeepYet());
    $updates->setParams(['stack' => $attic->id()->stored()]);
    $route = AStacksScreen::Updates->forTheStack($attic->id());

    $before = NativeComponent::markActive($updates);
    $observer->tree([], $route);
    $observer->tree([], $route);
    $observer->event([], null);
    $observer->nav([]);
    NativeComponent::restoreActive($before);

    expect($was->timesNoted())->toBe(1)
        ->and($was->tabOf($attic->id()))->toBe(WhichTab::Updates);
});

it('opens a stack tapped on the list on the tab last used there', function (): void {
    $loft = aStackLeftOff('The loft', 'a');
    $attic = aStackLeftOff('The attic', 'b');
    $was = WhereTheOperatorWasInMemory::nowhere();
    $was->wasOn($attic->id(), WhichTab::Repairs);

    $list = theListTheAppOpensOn(StacksInMemory::holding($loft, $attic), $was);

    expect($list->tappingGoesTo($attic))->toBe(AStacksScreen::Repairs->forTheStack($attic->id()))
        ->and($list->tappingGoesTo($loft))->toBe(AStacksScreen::Health->forTheStack($loft->id()));
});
