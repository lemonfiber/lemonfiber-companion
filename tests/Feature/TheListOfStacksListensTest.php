<?php

declare(strict_types=1);

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
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatThePhoneKeeps;

// The list of stacks the top bar's name opens says how each stack stands. On a
// screen that holds no stream, what the phone kept goes out of date within
// half a minute, so the list listens while it is open, by the rules the list
// of stacks the app opens on keeps, and lets go when it closes.

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

/** A tab on the cellar that holds no stream, with the list listening through `$hearing`. */
function aTabThatHearsThrough(AStackThatSpeaksUp $hearing, StandingsInMemory $standings, ?ACaptureInMemory $capture = null): HowCurrentThisStackIs
{
    $keychain = signedIntoBoth();
    $screen = new HowCurrentThisStackIs(
        AStackThatKeepsCurrent::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)),
        $keychain,
        AroundThePhone::holding(
            StacksInMemory::holding(theCellar(), theShed()),
            $standings,
            $keychain,
            FrozenClock::at(Instant::atEpochSeconds(LISTENING_AT)),
            $hearing,
            $capture,
        ),
        new AppsSettingsThatOpen(),
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
        $own,
        $clock,
        ACaptureInMemory::inFront(),
        $standings,
        WhatThePhoneKeeps::nothingYet(),
        new AppsSettingsThatOpen(),
        NoticingWhatIsNew::fromNothing(),
    );
    $screen->setParams(['stack' => theCellar()->id()->stored()]);

    return $screen;
}

/**
 * The word each row of the open list says, by name.
 *
 * @return array<string, string>
 */
function whatEachRowSays(HowCurrentThisStackIs $screen): array
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
    $screen = aTabThatHearsThrough(AStackThatSpeaksUp::holdingOpen(theStackIsCritical(), theStackIsCritical()), $standings);
    $screen->chooseAStack();

    $before = whatEachRowSays($screen);
    $screen->hearEachStackWhileChoosing();

    expect($before)->toBe([
        'The cellar' => HowItStands::Unknown->saidInAWord(),
        'The shed' => HowItStands::Unknown->saidInAWord(),
    ])->and(whatEachRowSays($screen))->toBe([
        'The cellar' => HowItStands::Critical->saidInAWord(),
        'The shed' => HowItStands::Critical->saidInAWord(),
    ]);
});

it('reaches no stack while the list is shut', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = aTabThatHearsThrough($hearing, StandingsInMemory::working());

    $screen->hearEachStackWhileChoosing();

    expect($hearing->asked())->toBe(0)
        ->and($hearing->lettingsGo())->toBe(0);
});

it('lets go of what it listens to however the list is shut, and when the screen stops', function (Closure $shutting): void {
    $hearing = AStackThatSpeaksUp::holdingOpen();
    $screen = aTabThatHearsThrough($hearing, StandingsInMemory::working());
    $screen->chooseAStack();
    $screen->hearEachStackWhileChoosing();

    $shutting($screen);

    expect($hearing->asked())->toBe(2)
        ->and($hearing->lettingsGo())->toBe(1);
})->with([
    'shut by hand' => [static fn(HowCurrentThisStackIs $screen) => $screen->stopChoosingAStack()],
    'a stack chosen' => [static fn(HowCurrentThisStackIs $screen) => $screen->openTheStack(theShed()->id()->stored())],
    'a stack added' => [static fn(HowCurrentThisStackIs $screen) => $screen->addAStack()],
    'the screen stopped' => [static fn(HowCurrentThisStackIs $screen) => $screen->stop()],
]);

it('stays open when the screen stops, and listens again when it is back', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen();
    $screen = aTabThatHearsThrough($hearing, StandingsInMemory::working());
    $screen->chooseAStack();
    $screen->stop();
    $screen->hearEachStackWhileChoosing();

    expect($screen->choosingAStack)->toBeTrue()
        ->and($hearing->asked())->toBe(2);
});

it('lets go rather than listening while nobody can see the list', function (): void {
    $hearing = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = aTabThatHearsThrough($hearing, StandingsInMemory::working(), ACaptureInMemory::away());
    $screen->chooseAStack();

    $screen->hearEachStackWhileChoosing();

    expect($hearing->asked())->toBe(0)
        ->and($hearing->lettingsGo())->toBe(1);
});

it('does not ask the health screen\'s own stack twice, and leaves its stream alone when the list shuts', function (): void {
    $own = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $list = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $screen = theCellarsHealth($own, $list, StandingsInMemory::working());
    $screen->listen();
    $screen->chooseAStack();

    $screen->hearEachStackWhileChoosing();
    $screen->stopChoosingAStack();

    expect($list->asked())->toBe(1)
        ->and($list->lettingsGo())->toBe(1)
        ->and($own->asked())->toBe(1)
        ->and($own->lettingsGo())->toBe(0);
});

it('lets go of its own stream and the list\'s when the health screen stops', function (): void {
    $own = AStackThatSpeaksUp::holdingOpen(theStackIsCritical());
    $list = AStackThatSpeaksUp::holdingOpen();
    $screen = theCellarsHealth($own, $list, StandingsInMemory::working());
    $screen->listen();
    $screen->chooseAStack();
    $screen->hearEachStackWhileChoosing();

    $screen->stop();

    expect($own->lettingsGo())->toBe(1)
        ->and($list->lettingsGo())->toBe(1);
});
