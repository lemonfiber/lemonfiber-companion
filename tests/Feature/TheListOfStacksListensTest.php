<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAlert;
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
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatTheWordsMean;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;

// The list of stacks the top bar's name opens says how each stack stands, and
// every stack the operator is signed in to is told of while the app runs. What
// the phone kept of the other stacks goes out of date within half a minute, so
// a screen listens to them, the list open or shut, by the rules the list of
// stacks the app opens on keeps, and lets go when it stops.

/** The moment the phone reads in every case below. */
const LISTENING_AT = 1_790_000_000;

/** Named for this file: the root suites share one namespace (`G10`). */
function aStackTheListHears(string $called, string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.90'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

function theCellar(): Stack
{
    return aStackTheListHears('The cellar', 'c');
}

function theShed(): Stack
{
    return aStackTheListHears('The shed', 'd');
}

/** A summary saying the stack is critical. */
function theStackIsCritical(): WhatWasHeard
{
    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Critical, 1, 'The tunnel is down', WhatStoppedMoving::nothing()));
}

/** A phone signed into both stacks as their operator. */
function signedIntoBoth(): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theCellar()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $keychain->keep(theShed()->id(), Session::of('another-session-not-a-secret'), Whose::theOperator());

    return $keychain;
}

/**
 * The health of the cellar, its own stream asked nothing in these cases, with the list listening through `$hearing`.
 *
 * The list leaves the cellar to the screen's own stream, so what it listens
 * for is every other stack: the shed.
 */
function aScreenThatHearsThrough(AStackThatSpeaksUp $hearing, StandingsInMemory $standings, ?ACaptureInMemory $capture = null): HowThisStackIs
{
    $keychain = signedIntoBoth();
    $clock = FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT));
    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theCellar(), theShed()), $standings, $keychain, $clock, $hearing, $capture),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(AStackThatSpeaksUp::holdingOpen(), $clock, standings: $standings),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    return $screen;
}

/** The health of the cellar, whose own stream is `$own` and whose list listens through `$list`. */
function theCellarsHealth(AStackThatSpeaksUp $own, AStackThatSpeaksUp $list, StandingsInMemory $standings): HowThisStackIs
{
    $keychain = signedIntoBoth();
    $clock = FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT));
    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theCellar(), theShed()), $standings, $keychain, $clock, $list),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($own, $clock, standings: $standings),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    return $screen;
}

/** The words the menu opens on the cellar, whose own stream is `$own` and whose list listens through `$list`. */
function theCellarsWords(AStackThatSpeaksUp $own, AStackThatSpeaksUp $list, StandingsInMemory $standings): WhatTheWordsMean
{
    $keychain = signedIntoBoth();
    $clock = FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT));
    $screen = new WhatTheWordsMean(
        AStackThatExplainsItsWords::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theCellar(), theShed()), $standings, $keychain, $clock, $list),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($own, $clock, standings: $standings),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    return $screen;
}

/**
 * The word each row of the open list says, by name.
 *
 * @return array<string, string>
 */
function whatEachRowSays(HowThisStackIs $screen): array
{
    $said = [];

    foreach ($screen->stacksToChooseFrom() as $row) {
        $said[$row->name] = $row->word;
    }

    return $said;
}

it('says how each stack stands now once the open list has listened, where what was kept had gone out of date', function (): void {
    $standings = StandingsInMemory::working()
        ->lastHeard(theCellar()->id(), HowItStands::Healthy, Instant::atEpochSeconds(LISTENING_AT - 600))
        ->lastHeard(theShed()->id(), HowItStands::Healthy, Instant::atEpochSeconds(LISTENING_AT - 600));
    $screen = aScreenThatHearsThrough(AStackThatSpeaksUp::holdingOpen(theStackIsCritical(), theStackIsCritical()), $standings);
    $screen->chooseAStack();

    $before = whatEachRowSays($screen);
    $screen->hearEachStack();

    expect($before['The shed'])->toBe(HowItStands::Unknown->saidInAWord())
        ->and(whatEachRowSays($screen)['The shed'])->toBe(HowItStands::Critical->saidInAWord());
});

it('hears every other stack the operator is signed in to, the list open or shut', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = aScreenThatHearsThrough($hearing, StandingsInMemory::working());

    $screen->hearEachStack();

    expect($hearing->asked())->toBe(1)
        ->and($hearing->lettingsGo())->toBe(0);
});

it('keeps listening when the list is shut, and lets go when the screen stops', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen();
    $screen = aScreenThatHearsThrough($hearing, StandingsInMemory::working());
    $screen->chooseAStack();
    $screen->hearEachStack();
    $screen->stopChoosingAStack();

    expect($hearing->lettingsGo())->toBe(0);

    $screen->stop();

    expect($hearing->asked())->toBe(1)
        ->and($hearing->lettingsGo())->toBe(1);
});

it('tells the operator each alert another stack raised, filed under its name', function (): void {
    $notifier = ANotifierInMemory::allowed();
    $raised = WhatWasHeard::aSignOfLife()->alerting(TheAlerts::of(AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, WhatTheCoreDecided::toSay('vpn.down-1'))));
    $keychain = signedIntoBoth();
    $clock = FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT));
    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theCellar(), theShed()), StandingsInMemory::working(), $keychain, $clock, AStackThatSpeaksUp::holdingOpen($raised), notifier: $notifier),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(AStackThatSpeaksUp::holdingOpen(), $clock),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    $screen->hearEachStack();

    expect($notifier->told())->toBe([sprintf('plain vpn.down %s onset under vpn.down-1', theShed()->name()->shown())]);
});

it('tells the operator each alert the screen\'s own stack raised, its resolution filed with its onset', function (): void {
    $notifier = ANotifierInMemory::allowed();
    $onset = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, WhatTheCoreDecided::toSay('vpn.down-1'));
    $resolved = AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Resolved, WhatTheCoreDecided::toSay('vpn.down-1'));
    $keychain = signedIntoBoth();
    $clock = FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT));
    $screen = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding(theCellar(), theShed()), StandingsInMemory::working(), $keychain, $clock, AStackThatSpeaksUp::holdingOpen()),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(AStackThatSpeaksUp::holdingOpen(theStackIsCritical()->alerting(TheAlerts::of($onset, $resolved))), $clock, notifier: $notifier),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    $screen->listen();

    expect($notifier->told())->toBe([
        sprintf('plain vpn.down %s onset under vpn.down-1', theCellar()->name()->shown()),
        sprintf('plain vpn.down %s resolved under vpn.down-1', theCellar()->name()->shown()),
    ]);
});

it('stays open when the screen stops, and listens again when it is back', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen();
    $screen = aScreenThatHearsThrough($hearing, StandingsInMemory::working());
    $screen->chooseAStack();
    $screen->stop();
    $screen->hearEachStack();

    expect($screen->choosingAStack)->toBeTrue()
        ->and($hearing->asked())->toBe(1);
});

it('lets go rather than listening while nobody can see the list', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = aScreenThatHearsThrough($hearing, StandingsInMemory::working(), ACaptureInMemory::away());
    $screen->chooseAStack();

    $screen->hearEachStack();

    expect($hearing->asked())->toBe(0)
        ->and($hearing->lettingsGo())->toBe(1);
});

it('does not ask an operator\'s screen\'s own stack twice, and leaves every stream alone when the list shuts', function (string $which): void {
    $own = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $list = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = $which === 'the health of the stack'
        ? theCellarsHealth($own, $list, StandingsInMemory::working())
        : theCellarsWords($own, $list, StandingsInMemory::working());
    $screen->listen();
    $screen->chooseAStack();

    $screen->hearEachStack();
    $screen->stopChoosingAStack();

    expect($list->asked())->toBe(1)
        ->and($list->lettingsGo())->toBe(0)
        ->and($own->asked())->toBe(1)
        ->and($own->lettingsGo())->toBe(0);
})->with(['the health of the stack', 'the words the menu opens']);

it('lets go of its own stream and the list\'s when the health screen stops', function (): void {
    $own = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $list = AStackThatSpeaksUp::holdingOpen();
    $screen = theCellarsHealth($own, $list, StandingsInMemory::working());
    $screen->listen();
    $screen->chooseAStack();
    $screen->hearEachStack();

    $screen->stop();

    expect($own->lettingsGo())->toBe(1)
        ->and($list->lettingsGo())->toBe(1);
});
