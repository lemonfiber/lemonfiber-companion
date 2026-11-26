<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AJobFollowed;
use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\HowTheCopyRunningWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Kernel\Api\WhatWasHeardFromAJob;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\NotAJobItFollows;
use Modules\Operator\Internal\PluginActFollowed;
use Modules\Operator\Internal\Presenters\HowTheCopyRunningReads;
use Modules\Operator\Internal\RepairsFollowed;
use Modules\Operator\Internal\Screens\TakingACopyHere;
use Modules\Operator\Internal\TheOnlyJob;
use Modules\Operator\Internal\ViewModels\APaceAsShown;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatCopies;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatTakesCopies;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatAScopeSays;
use Tests\Support\WhatTheDeviceWouldDraw;

// Taking a copy: the choice of what to copy, the question naming it, the
// copy being taken, and the stack's report of it.
//
// Here rather than in the operator module's own tests because a screen
// renders, and rendering needs the application.


/**
 * One line of the catalogue, as text, for filling another.
 *
 * @param array<string, string> $with
 */
function aLineOfTheCopy(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

/** The machine a copy is taken of. */
function theStackACopyIsTakenOf(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The screen, with a stack it knows and a keychain holding whatever a case says. */
function theCopyingScreen(
    AStackThatTakesCopies $copying,
    ?Supervising $supervising = null,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
    ?HearingTheJobs $hearing = null,
    ?AStackThatCopies $running = null,
    ?ACaptureInMemory $capture = null,
    ?FrozenClock $clock = null,
): TakingACopyHere {
    $stack = theStackACopyIsTakenOf();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $listening = AroundThePhone::listening(clock: $clock ?? FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)), capture: $capture ?? ACaptureInMemory::inFront(), copying: $running ?? AStackThatCopies::copyingNothing());
    $screen = new TakingACopyHere($copying, $supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), $listening, $hearing ?? AJobThatSays::nothing());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** A report of a copy, with what a case is about changed. */
function aCopyReported(
    ScopeOfACopy $scope,
    TheCopies $pruned,
    bool $brisk,
    WhetherItHoldsASecret $holds,
    WhetherItWasRehearsed $was,
): ACopyTaken {
    return ACopyTaken::reported($scope, $pruned, HowACopyPaced::measured(moved: 1_200_000_000, budget: 600_000_000, brisk: $brisk), $holds, $was);
}

/** A stack that has finished taking the copy, and reports it so. */
function aStackThatFinishedTaking(ACopyTaken $report): AStackThatTakesCopies
{
    return AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::done($report));
}

/** A screen that has asked for a copy of the whole stack and agreed to it. */
function aCopyAgreedTo(AStackThatTakesCopies $copying, ?AKeychainInMemory $keychain = null): TakingACopyHere
{
    $screen = theCopyingScreen($copying, keychain: $keychain);
    $screen->copyTheWholeStack();
    $screen->agree();

    return $screen;
}

/**
 * Every field of what became of a copy, in one comparable row.
 *
 * @return array<string, mixed>
 */
function everythingAboutTheCopy(HowTheCopyWent $went): array
{
    return [
        'cameBack' => $went->stands->went->cameBack(),
        'isSignedIn' => $went->stands->went->isSignedIn,
        'met' => $went->stands->went->met,
        'wasAsked' => $went->stands->wasAsked(),
        'isWorking' => $went->stands->isWorking(),
        'hasEnded' => $went->stands->hasEnded(),
        'wasRehearsed' => $went->wasRehearsed,
        'scope' => $went->scope instanceof AScopeAsShown ? [$went->scope->said, $went->scope->with, $went->scope->trees] : null,
        'tookSaid' => $went->tookSaid,
        'pruned' => $went->pruned,
        'prunedSaid' => $went->prunedSaid,
        'pace' => $went->pace instanceof APaceAsShown ? [$went->pace->said, $went->pace->moved->figure, $went->pace->moved->unit, $went->pace->budget->figure, $went->pace->budget->unit] : null,
        'holdsSaid' => $went->holdsSaid,
    ];
}

/**
 * What a state with no report says of every field, changed where a case says.
 *
 * @param  array<string, mixed> $changed
 * @return array<string, mixed>
 */
function nothingReportedOfTheCopy(array $changed = []): array
{
    return [
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'wasAsked' => true,
        'isWorking' => false,
        'hasEnded' => false,
        'wasRehearsed' => false,
        'scope' => null,
        'tookSaid' => null,
        'pruned' => [],
        'prunedSaid' => null,
        'pace' => null,
        'holdsSaid' => null,
        ...$changed,
    ];
}

it('offers the whole stack and each service the stack runs, and asks nothing yet', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->offers();

    // Each service is a row that reads its name and answers a screen reader
    // with what tapping it asks for, which its name alone does not say.
    expect($drawn)->toBe([__('stacks.copy.the_whole_stack'), 'Sonarr', 'Jellyfin', __('health.ask_again')])
        ->and(json_encode(WhatTheDeviceWouldDraw::tree($screen)))->toContain(__('stacks.copy.only_this_service', ['name' => 'Sonarr']))
        ->and($screen->askingAbout())->toBeNull()
        ->and(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy(['wasAsked' => false]))
        ->and($copying->taken())->toBe([]);
});

it('names the whole stack before anything is taken, and takes nothing until the yes', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $screen->copyTheWholeStack();

    $asking = $screen->askingAbout();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($asking?->said)->toBe('stacks.copy.scope.whole_stack')
        ->and($asking?->with)->toBe([])
        ->and($asking?->trees)->toBe([])
        ->and($drawn)->toContain(__('stacks.copy.about_to', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->toContain(__('stacks.copy.may_remove'))
        ->and($copying->taken())->toBe([]);
});

it('names one service before anything is taken, and only a service the stack listed', function (): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()));
    $screen->copyTheService('jellyfin');

    expect($screen->askingAbout()?->said)->toBe('stacks.copy.scope.service')
        ->and($screen->askingAbout()?->with)->toBe(['name' => 'jellyfin'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.copy.about_to', ['scope' => aLineOfTheCopy('stacks.copy.scope.service', ['name' => 'jellyfin'])]));
});

it('asks about nothing for a service the stack did not list, or for no name', function (string $named): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()));
    $screen->copyTheService($named);

    expect($screen->askingAbout())->toBeNull();
})->with(['not listed' => ['radarr'], 'blank' => ['  ']]);

it('leaves it where the operator says never mind', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $screen->copyTheWholeStack();
    $screen->neverMind();

    expect($screen->askingAbout())->toBeNull()
        ->and($copying->taken())->toBe([]);
});

it('sends nothing on a yes nobody was asked for', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $screen->agree();

    expect($copying->taken())->toBe([])
        ->and($screen->lastCopy()->stands->wasAsked())->toBeFalse();
});

it('takes exactly the copy it named, and says it is being taken with how far it has got as the stack reads it', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying, running: AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working())));
    $screen->copyTheService('sonarr');
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect(array_map(static fn(ACopyAsked $asked): string => WhatAScopeSays::of($asked->scope()), $copying->taken()))->toBe(['service:sonarr'])
        ->and($screen->askingAbout())->toBeNull()
        ->and(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy([
            'isWorking' => true,
            'scope' => ['stacks.copy.scope.service', ['name' => 'sonarr'], []],
        ]))
        ->and($drawn)->toContain(__('stacks.copy.taking', ['scope' => aLineOfTheCopy('stacks.copy.scope.service', ['name' => 'sonarr'])]))
        ->and($drawn)->toContain(...howFarTheCopyHasGot())
        ->and($copying->followed())->toBe([]);
});

it('asks after a copy on its cadence while it runs, by the handle it was answered with', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = aCopyAgreedTo($copying);
    $screen->whileItRuns();
    $screen->lastCopy();

    expect($copying->followed())->toHaveCount(1)
        ->and($copying->followed()[0]->shown())->toBe(AStackThatTakesCopies::THE_JOB)
        ->and(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy([
            'isWorking' => true,
            'scope' => ['stacks.copy.scope.whole_stack', [], []],
        ]));
});

it('does not ask again on its cadence once the copy has finished', function (): void {
    $copying = aStackThatFinishedTaking(aCopyReported(ScopeOfACopy::theWholeStack(), TheCopies::named(), brisk: true, holds: WhetherItHoldsASecret::Secret, was: WhetherItWasRehearsed::CarriedOut));
    $screen = aCopyAgreedTo($copying);
    $screen->whileItRuns();
    $screen->lastCopy();
    $screen->whileItRuns();
    $screen->lastCopy();

    expect($copying->followed())->toHaveCount(1);
});

it('reports what the copy covered, what it removed, its size against the minute and that it holds credentials', function (): void {
    $copying = aStackThatFinishedTaking(aCopyReported(
        ScopeOfACopy::theWholeStack(),
        TheCopies::named('lemonfiber-20260901-0300-full', 'lemonfiber-20260902-0300-full'),
        brisk: false,
        holds: WhetherItHoldsASecret::Secret,
        was: WhetherItWasRehearsed::CarriedOut,
    ));
    $screen = aCopyAgreedTo($copying);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe([
        'cameBack' => true,
        'isSignedIn' => true,
        'met' => '',
        'wasAsked' => true,
        'isWorking' => false,
        'hasEnded' => false,
        'wasRehearsed' => false,
        'scope' => ['stacks.copy.scope.whole_stack', [], []],
        'tookSaid' => 'stacks.copy.copied',
        'pruned' => ['lemonfiber-20260901-0300-full', 'lemonfiber-20260902-0300-full'],
        'prunedSaid' => 'stacks.copy.removed',
        'pace' => ['stacks.copy.pace.slow', 1, 'household.gigabytes', 600, 'household.megabytes'],
        'holdsSaid' => 'stacks.copy.holds_credentials',
    ])
        ->and($drawn)->toContain(__('stacks.copy.copied', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->toContain(trans_choice('stacks.copy.removed', 2))
        ->and($drawn)->toContain('lemonfiber-20260901-0300-full')
        ->and($drawn)->toContain('lemonfiber-20260902-0300-full')
        ->and($drawn)->toContain(__('stacks.copy.pace.slow', ['moved' => 1, 'moved_unit' => aLineOfTheCopy('household.gigabytes'), 'budget' => 600, 'budget_unit' => aLineOfTheCopy('household.megabytes')]))
        ->and($drawn)->toContain(__('stacks.copy.holds_credentials'))
        ->and($drawn)->not->toContain(__('stacks.a_rehearsal'))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(__('stacks.copy.see_the_copies'));
});

it('says a copy that removed nothing kept every other one', function (): void {
    $screen = aCopyAgreedTo(aStackThatFinishedTaking(aCopyReported(ScopeOfACopy::theWholeStack(), TheCopies::named(), brisk: true, holds: WhetherItHoldsASecret::Plain, was: WhetherItWasRehearsed::CarriedOut)));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->lastCopy()->pruned)->toBe([])
        ->and($drawn)->toContain(trans_choice('stacks.copy.removed', 0))
        ->and($drawn)->toContain(__('stacks.copy.kept_every_other'))
        ->and($screen->lastCopy()->pace?->said)->toBe('stacks.copy.pace.brisk')
        ->and($screen->lastCopy()->holdsSaid)->toBe('stacks.copy.holds_no_credentials');
});

it('labels a rehearsal as one and words every sentence of it as what would happen', function (bool $brisk, WhetherItHoldsASecret $holds, string $pace, string $holdsSaid): void {
    $screen = aCopyAgreedTo(aStackThatFinishedTaking(aCopyReported(ScopeOfACopy::theWholeStack(), TheCopies::named('lemonfiber-20260901-0300-full'), brisk: $brisk, holds: $holds, was: WhetherItWasRehearsed::Rehearsed)));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->lastCopy()->wasRehearsed)->toBeTrue()
        ->and($screen->lastCopy()->tookSaid)->toBe('stacks.copy.would_copy')
        ->and($screen->lastCopy()->prunedSaid)->toBe('stacks.copy.would_remove')
        ->and($screen->lastCopy()->pace?->said)->toBe($pace)
        ->and($screen->lastCopy()->holdsSaid)->toBe($holdsSaid)
        ->and($drawn)->toContain(__('stacks.a_rehearsal'))
        ->and($drawn)->toContain(__('stacks.copy.would_copy', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->toContain(trans_choice('stacks.copy.would_remove', 1))
        ->and($drawn)->not->toContain(__('stacks.copy.copied', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->not->toContain(trans_choice('stacks.copy.removed', 1));
})->with([
    'inside the minute, with credentials' => [true, WhetherItHoldsASecret::Secret, 'stacks.copy.pace.would_be_brisk', 'stacks.copy.would_hold_credentials'],
    'past it, with none' => [false, WhetherItHoldsASecret::Plain, 'stacks.copy.pace.would_be_slow', 'stacks.copy.would_hold_no_credentials'],
]);

it('names an existing setup and the trees it read, where that is what was copied', function (): void {
    $screen = aCopyAgreedTo(aStackThatFinishedTaking(aCopyReported(
        ScopeOfACopy::anExistingSetup(AnExistingSetup::of('media', WhatACopyHolds::these('/srv/arr', '/srv/plex'))),
        TheCopies::named(),
        brisk: true,
        holds: WhetherItHoldsASecret::Secret,
        was: WhetherItWasRehearsed::CarriedOut,
    )));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->lastCopy()->scope?->said)->toBe('stacks.copy.scope.existing')
        ->and($screen->lastCopy()->scope?->with)->toBe(['name' => 'media'])
        ->and($screen->lastCopy()->scope?->trees)->toBe(['/srv/arr', '/srv/plex'])
        ->and($drawn)->toContain(__('stacks.copy.copied', ['scope' => aLineOfTheCopy('stacks.copy.scope.existing', ['name' => 'media'])]))
        ->and($drawn)->toContain('/srv/arr')
        ->and($drawn)->toContain('/srv/plex');
});

it('says a copy the stack has no outcome for is not known, rather than failed', function (): void {
    $screen = aCopyAgreedTo(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::noOutcome()));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy([
        'hasEnded' => true,
        'scope' => ['stacks.copy.scope.whole_stack', [], []],
    ]))
        ->and($drawn)->toContain(__('stacks.copy.no_outcome', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->toContain(__('stacks.copy.no_outcome_action'));
});

it('says what stood in the way of a copy the stack would not take, and asks again from the start', function (): void {
    $copying = AStackThatTakesCopies::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = aCopyAgreedTo($copying);

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy(['cameBack' => false, 'met' => KindOfObstacle::StackDidNotAnswer->said()]))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->lastCopy()->stands->wasAsked())->toBeFalse()
        ->and($copying->followed())->toBe([]);
});

it('lets go of a session the stack refused, while taking or while asking after', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $copying = $whileAsking
        ? AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)))
        : AStackThatTakesCopies::met(Obstacle::of(KindOfObstacle::CredentialWasRefused));
    $screen = aCopyAgreedTo($copying, $keychain);

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->lastCopy()->stands->went->isSignedIn)->toBeFalse()
        ->and($screen->lastCopy()->stands->wasAsked())->toBeTrue()
        ->and($keychain->isHolding(theStackACopyIsTakenOf()->id()))->toBeFalse();
})->with(['taking' => [false], 'asking after' => [true]]);

it('asks for a session rather than a copy where this device holds none', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying, signedIn: false);
    $screen->copyTheWholeStack();
    $screen->agree();

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy(['isSignedIn' => false, 'cameBack' => false]))
        ->and($copying->taken())->toBe([]);

    $fresh = theCopyingScreen($copying, signedIn: false);

    expect($fresh->answer()->went->isSignedIn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($fresh)->offers())->toContain(__('connection.sign_in'))
        ->and(WhatTheDeviceWouldDraw::by($fresh)->offers())->not->toContain(__('stacks.copy.the_whole_stack'));
});

it('lets go of a session refused while it follows a copy after the session is gone', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = aCopyAgreedTo(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), $keychain);
    $keychain->forget(theStackACopyIsTakenOf()->id());
    $screen->whileItRuns();

    expect($screen->lastCopy()->stands->went->isSignedIn)->toBeFalse();
});

it('offers nothing to copy where the services could not be listed, and says what stood in the way', function (): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), AStackThatSupervises::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->not->toContain(__('stacks.copy.the_whole_stack'))
        ->and($drawn->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));

    $screen->copyTheService('sonarr');

    expect($screen->askingAbout())->toBeNull();
});

it('says a stack running no service has none to copy on its own', function (): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), AStackThatSupervises::with(Daemons::none(WhatAMachineRuns::whatTheVerbsCost())));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.copy.no_services'));
});

it('asks the stack again, the services and the copy both', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying, $supervising);
    $screen->answer();
    $screen->copyTheWholeStack();
    $screen->agree();
    $screen->again();
    $screen->answer();
    $screen->lastCopy();

    expect($supervising->askings())->toBe(2)
        ->and($copying->followed())->toHaveCount(1);
});

it('asks the stack for its services once, however often a frame reads them', function (): void {
    $supervising = AStackThatSupervises::with(WhatAMachineRuns::twoThings());
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), $supervising);
    $screen->answer();
    $screen->answer();

    expect($supervising->askings())->toBe(1);
});

it('follows nothing where it holds a job but not the copy the job was for', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $screen->followed = [TheOnlyJob::Followed->name => AJobFollowed::named(Job::named(AStackThatTakesCopies::THE_JOB))];

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy(['wasAsked' => false]))
        ->and($copying->followed())->toBe([]);
});

it('answers for the one job it follows and for no other it is made to follow', function (bool $signedIn): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), signedIn: $signedIn);
    $screen->followed = [PluginActFollowed::TheAct->name => AJobFollowed::named(Job::named(AStackThatTakesCopies::THE_JOB))];
    $screen->followedFor = [PluginActFollowed::TheAct->name => PluginActFollowed::TheAct];
    $unfollowed = Closure::bind(static fn(TakingACopyHere $asked): HowTheCopyWent => $asked->whatBecameOfTheJob(RepairsFollowed::TheYes), null, TakingACopyHere::class);

    expect($screen->whileItRuns(...))->toThrow(NotAJobItFollows::class)
        ->and(static fn(): HowTheCopyWent => $unfollowed($screen))->toThrow(NotAJobItFollows::class);
})->with(['signed in' => [true], 'signed out' => [false]]);

it('refuses a route parameter that is not text as naming a stack', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped, and anything that is not a string names no
    // stack.
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

/** A copy of the whole stack agreed to, on a stack whose job says these in turn. */
function aCopyWhoseJobSays(AStackThatTakesCopies $copying, WhatWasHeardFromAJob ...$said): TakingACopyHere
{
    $screen = theCopyingScreen($copying, hearing: AJobThatSays::saying(...$said));
    $screen->copyTheWholeStack();
    $screen->agree();

    return $screen;
}

/** A copy still being taken. */
function aCopyStillBeingTaken(): AStackThatTakesCopies
{
    return AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
}

it('draws the newest line the job said in the stack\'s words, and offers the rest', function (): void {
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken(), WhatWasHeardFromAJob::said('Stopping sonarr', 'Writing the archive'));
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain('Writing the archive')
        ->and($drawn->said())->not->toContain('Stopping sonarr')
        ->and($drawn->offers())->toContain(__('stacks.what_it_said.show_all'));
});

it('opens every line the job said, oldest first, with how many it keeps, and closes them again', function (): void {
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken(), WhatWasHeardFromAJob::said('Stopping sonarr'), WhatWasHeardFromAJob::said('Writing the archive'));
    $screen->whileItRuns();
    $screen->whileItRuns();
    $screen->showAllItSaid();
    $all = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->showTheNewestItSaid();
    $newest = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_values(array_intersect($all, ['Stopping sonarr', 'Writing the archive'])))->toBe(['Stopping sonarr', 'Writing the archive'])
        ->and($all)->toContain(__('stacks.what_it_said.kept', ['count' => HowManyLines::ON_A_PHONE]))
        ->and($all)->toContain(__('stacks.what_it_said.show_the_newest'))
        ->and($newest)->not->toContain('Stopping sonarr')
        ->and($newest)->toContain('Writing the archive');
});

it('opens the lines of no job a tap names that is not followed', function (): void {
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken(), WhatWasHeardFromAJob::said('Stopping sonarr', 'Writing the archive'));
    $screen->whileItRuns();
    $screen->showAllItSaid('NotFollowed');

    expect($screen->showingAllItSaid)->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain('Stopping sonarr');
});

it('says how many older lines it let go of, once there are more than it keeps', function (): void {
    $lines = array_map(static fn(int $line): string => sprintf('Line %d', $line), range(1, HowManyLines::ON_A_PHONE + 2));
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken(), WhatWasHeardFromAJob::said(...$lines));
    $screen->whileItRuns();
    $screen->showAllItSaid();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->not->toContain('Line 2')
        ->and($drawn)->toContain('Line 3')
        ->and($drawn)->toContain(trans_choice('stacks.what_it_said.let_go', 2));
});

it('draws nothing of the job and offers nothing more while it has said nothing', function (): void {
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken());
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->not->toContain(__('stacks.what_it_said.show_all'));
});

it('draws a line as the text it is, never as markup', function (): void {
    $said = '<b>Writing</b> & {{ $archive }}';
    $screen = aCopyWhoseJobSays(aCopyStillBeingTaken(), WhatWasHeardFromAJob::said($said));
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain($said);
});

it('asks for the lines of the job the copy answered with, and keeps the last line heard when the stream could not be heard', function (): void {
    $hearing = AJobThatSays::saying(WhatWasHeardFromAJob::said('Writing the archive'), WhatWasHeardFromAJob::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), hearing: $hearing);
    $screen->copyTheWholeStack();
    $screen->agree();
    $screen->whileItRuns();
    $screen->whileItRuns();

    expect(array_map(static fn(Job $job): string => $job->shown(), $hearing->asked()))->toBe([AStackThatTakesCopies::THE_JOB, AStackThatTakesCopies::THE_JOB])
        ->and($screen->whatItSays()->newest)->toBe('Writing the archive')
        ->and($screen->awaitsAnOutcome())->toBeTrue();
});

it('takes the last lines and lets go of the stream once the job has finished, and asks for none after', function (): void {
    $hearing = AJobThatSays::saying(WhatWasHeardFromAJob::said('Writing the archive'), WhatWasHeardFromAJob::said('Pruned two older copies'));
    $screen = theCopyingScreen(aStackThatFinishedTaking(aCopyReported(ScopeOfACopy::theWholeStack(), TheCopies::named(), brisk: true, holds: WhetherItHoldsASecret::Secret, was: WhetherItWasRehearsed::CarriedOut)), hearing: $hearing);
    $screen->copyTheWholeStack();
    $screen->agree();
    $lettingsGo = $hearing->lettingsGo();
    $screen->whileItRuns();
    $screen->lastCopy();
    $screen->whileItRuns();

    expect($hearing->asked())->toHaveCount(2)
        ->and($hearing->lettingsGo())->toBe($lettingsGo + 1)
        ->and($hearing->placesKept())->toBe(1)
        ->and($screen->whatItSays()->newest)->toBe('Pruned two older copies')
        ->and($screen->awaitsAnOutcome())->toBeFalse();
});

it('follows a new job afresh, forgetting what the one before it said and where its stream left off', function (): void {
    $hearing = AJobThatSays::saying(WhatWasHeardFromAJob::said('Writing the archive'));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), hearing: $hearing);
    $screen->copyTheWholeStack();
    $screen->agree();
    $screen->whileItRuns();
    $screen->showAllItSaid();
    $screen->copyTheWholeStack();
    $screen->agree();

    expect($screen->whatItSays()->newest)->toBeNull()
        ->and($screen->whatItSays()->showingAll)->toBeFalse()
        ->and($hearing->placesKept())->toBe(0);
});

it('follows nothing, and so awaits nothing, before the operator sends anything', function (): void {
    $screen = theCopyingScreen(aCopyStillBeingTaken());
    $screen->whileItRuns();

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->whatItSays()->newest)->toBeNull();
});

it('lets go of the job\'s stream when the screen stops', function (): void {
    $hearing = AJobThatSays::saying(WhatWasHeardFromAJob::said('Writing the archive'));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), hearing: $hearing);
    $screen->copyTheWholeStack();
    $screen->agree();
    $screen->whileItRuns();
    $lettingsGo = $hearing->lettingsGo();
    $screen->stop();

    expect($hearing->lettingsGo())->toBe($lettingsGo + 1)
        ->and($hearing->placesKept())->toBe(0)
        ->and($screen->whatItSays()->newest)->toBe('Writing the archive');
});

/** A reading of a copy running, as a case moves it, taken ten minutes before the screen's moment. */
function aCopyRunningThatIs(HowTheCopyMoves $moves, WhatIsCopied $what = WhatIsCopied::Backup): HowTheCopyRunningWasRead
{
    return HowTheCopyRunningWasRead::said(WhatIsBeingCopied::running(new ACopyRunning($what, ScopeOfACopy::theWholeStack(), aMomentOfTheCopy(-600), 734_003_200, 2_147_483_648, 'sonarr database', $moves)));
}

function aMomentOfTheCopy(int $seconds): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $seconds);
}

/**
 * How much of how much a copy running has written, and the part it writes now, as drawn.
 *
 * @return list<string>
 */
function howFarTheCopyHasGot(): array
{
    $written = HowBig::of(734_003_200);
    $whole = HowBig::of(2_147_483_648);

    return [
        aLineOfTheCopy('stacks.copy.running.written', ['written' => (string) $written->figure, 'written_unit' => aLineOfTheCopy($written->said), 'whole' => (string) $whole->figure, 'whole_unit' => aLineOfTheCopy($whole->said)]),
        aLineOfTheCopy('stacks.copy.running.part', ['part' => 'sonarr database']),
        trans_choice('stacks.copy.running.started.minutes', 10),
    ];
}

function aCopyStuckFor(int $minutes): string
{
    return trans_choice('stacks.copy.running.stalled.minutes', $minutes);
}

function aCopySlowedTo(int $bytes): string
{
    $limit = HowBig::of($bytes);

    return aLineOfTheCopy(HowTheCopyRunningReads::PACED, ['limit' => (string) $limit->figure, 'limit_unit' => aLineOfTheCopy($limit->said)]);
}

it('reads the copy running once when it opens mid-copy, and draws how much of how much, the part now and that it is writing', function (): void {
    $running = AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working()));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), running: $running);
    $first = WhatTheDeviceWouldDraw::by($screen)->said();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect($running->read())->toBe(1)
        ->and($first)->not->toContain(aLineOfTheCopy('stacks.copy.running.what.backup', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]))
        ->and($drawn)->toContain(aLineOfTheCopy('stacks.copy.running.what.backup', ['scope' => aLineOfTheCopy('stacks.copy.scope.whole_stack')]), ...howFarTheCopyHasGot())
        ->and($drawn)->toContain(aLineOfTheCopy(HowTheCopyRunningReads::WORKING))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->toContain(aLineOfTheCopy('navigation.menu.copies_run'))
        ->and($running->read())->toBe(1);
});

it('says a paced copy is slowed on purpose to the limit and a stalled one is stuck with how long nothing moved, as the stack says each', function (): void {
    $paced = WhatTheDeviceWouldDraw::onTheSecondFrame(theCopyingScreen(aCopyStillBeingTaken(), running: AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::paced(52_428_800)))))->said();
    $stalled = WhatTheDeviceWouldDraw::onTheSecondFrame(theCopyingScreen(aCopyStillBeingTaken(), running: AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::stalled(aMomentOfTheCopy(-420))))))->said();

    expect($paced)->toContain(aCopySlowedTo(52_428_800))
        ->and($paced)->not->toContain(aCopyStuckFor(7), aLineOfTheCopy(HowTheCopyRunningReads::WORKING))
        ->and($stalled)->toContain(aCopyStuckFor(7))
        ->and($stalled)->not->toContain(aCopySlowedTo(52_428_800), aLineOfTheCopy(HowTheCopyRunningReads::WORKING));
});

it('says no copy is running where the stack says none is', function (): void {
    expect(WhatTheDeviceWouldDraw::onTheSecondFrame(theCopyingScreen(aCopyStillBeingTaken()))->said())->toContain(aLineOfTheCopy(HowTheCopyRunningReads::NONE));
});

it('says it cannot tell, drawing neither working nor stalled, where the stack gives no reading', function (): void {
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame(theCopyingScreen(aCopyStillBeingTaken(), running: AStackThatCopies::holdingOpen(HowTheCopyRunningWasRead::refused(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)))))->said();

    expect($drawn)->toContain(aLineOfTheCopy(HowTheCopyRunningReads::CANNOT_TELL))
        ->and($drawn)->not->toContain(aLineOfTheCopy(HowTheCopyRunningReads::WORKING), aCopyStuckFor(7), aLineOfTheCopy(HowTheCopyRunningReads::NONE));
});

it('takes what the stream says in place of what it read, and cannot tell once the stream no longer vouches for it', function (): void {
    $running = AStackThatCopies::thenEnding(aCopyRunningThatIs(HowTheCopyMoves::working()), WhatAStreamSaid::said(WhatIsBeingCopied::running(new ACopyRunning(WhatIsCopied::Backup, ScopeOfACopy::theWholeStack(), aMomentOfTheCopy(-600), 734_003_200, 2_147_483_648, 'sonarr database', HowTheCopyMoves::stalled(aMomentOfTheCopy(-420))))));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), running: $running);
    $screen->followTheCopy();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->followTheCopy();
    $heard = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->followTheCopy();
    $ended = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->followTheCopy();

    expect($running->read())->toBe(1)
        ->and($running->asked())->toBe(2)
        ->and($heard)->toContain(aCopyStuckFor(7))
        ->and($ended)->toContain(aLineOfTheCopy(HowTheCopyRunningReads::CANNOT_TELL))
        ->and($ended)->not->toContain(aCopyStuckFor(7));
});

it('lets go of a stream gone quiet past the contract\'s silence', function (): void {
    $running = AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working()));
    $clock = FrozenClock::at(aMomentOfTheCopy(0));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), running: $running, clock: $clock);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->followTheCopy();
    $clock->moveTo(aMomentOfTheCopy(3_600));
    $screen->followTheCopy();

    expect($running->lettingsGo())->toBe(1)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineOfTheCopy(HowTheCopyRunningReads::CANNOT_TELL));
});

it('never asks with a member\'s session, nor without one, and draws nothing of a copy', function (bool $member): void {
    $running = AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working()), WhatAStreamSaid::said(WhatIsBeingCopied::nothing()));
    $keychain = AKeychainInMemory::working();

    if ($member) {
        $keychain->keep(theStackACopyIsTakenOf()->id(), Session::of('a-session-not-a-secret'), Whose::member('a7f3'));
    }

    $screen = theCopyingScreen(aCopyStillBeingTaken(), keychain: $keychain, signedIn: false, running: $running);
    $shown = $screen->theCopyRunning();
    $screen->followTheCopy();
    $drawn = (string) json_encode(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said());

    expect($running->read())->toBe(0)
        ->and($running->asked())->toBe(0)
        ->and($shown)->toBeNull()
        ->and($screen->copyIsShown)->toBeFalse()
        ->and($drawn)->not->toContain(aLineOfTheCopy(HowTheCopyRunningReads::CANNOT_TELL), aLineOfTheCopy(HowTheCopyRunningReads::NONE), aLineOfTheCopy('navigation.menu.copies_run'));
})->with(['a member\'s session' => true, 'no session' => false]);

it('lets go of the copy\'s stream when nobody can see the screen, and when the screen stops', function (): void {
    $running = AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working()));
    $capture = ACaptureInMemory::away();
    $screen = theCopyingScreen(aCopyStillBeingTaken(), running: $running, capture: $capture);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->followTheCopy();
    $away = $running->lettingsGo();
    $capture->cameBack();
    $screen->stop();

    expect($running->asked())->toBe(0)
        ->and($away)->toBe(1)
        ->and($running->lettingsGo())->toBe(2);
});

it('reads what is being copied again when the operator asks again', function (): void {
    $running = AStackThatCopies::copyingNothing();
    $screen = theCopyingScreen(aCopyStillBeingTaken(), running: $running);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($running->read())->toBe(2);
});

it('lets go of a session the stack refused on the copy\'s stream, and then cannot tell', function (): void {
    $keychain = AKeychainInMemory::working();
    $keychain->keep(theStackACopyIsTakenOf()->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $running = AStackThatCopies::holdingOpen(aCopyRunningThatIs(HowTheCopyMoves::working()), WhatAStreamSaid::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)));
    $screen = theCopyingScreen(aCopyStillBeingTaken(), keychain: $keychain, signedIn: false, running: $running);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->followTheCopy();

    expect($keychain->isHolding(theStackACopyIsTakenOf()->id()))->toBeFalse()
        ->and($screen->theCopyRunning()?->said)->toBe(HowTheCopyRunningReads::CANNOT_TELL);
});
