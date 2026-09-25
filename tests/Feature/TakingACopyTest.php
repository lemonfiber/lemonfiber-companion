<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\TakingACopyHere;
use Modules\Operator\Internal\ViewModels\APaceAsShown;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatTakesCopies;
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
): TakingACopyHere {
    $stack = theStackACopyIsTakenOf();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new TakingACopyHere($copying, $supervising ?? AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, StacksInMemory::holding($stack));
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
        'cameBack' => $went->went->cameBack(),
        'isSignedIn' => $went->went->isSignedIn,
        'met' => $went->went->met,
        'wasAsked' => $went->wasAsked,
        'isWorking' => $went->isWorking,
        'hasEnded' => $went->hasEnded,
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

    expect($drawn)->toContain(__('stacks.copy.the_whole_stack'))
        ->and($drawn)->toContain(__('stacks.copy.only', ['name' => 'Sonarr']))
        ->and($drawn)->toContain(__('stacks.copy.only', ['name' => 'Jellyfin']))
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
        ->and($screen->lastCopy()->wasAsked)->toBeFalse();
});

it('takes exactly the copy it named, and says it is being taken without drawing progress nobody measured', function (): void {
    $copying = AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning());
    $screen = theCopyingScreen($copying);
    $screen->copyTheService('sonarr');
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect(array_map(static fn(ACopyAsked $asked): string => WhatAScopeSays::of($asked->scope()), $copying->taken()))->toBe(['service:sonarr'])
        ->and($screen->askingAbout())->toBeNull()
        ->and(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy([
            'isWorking' => true,
            'scope' => ['stacks.copy.scope.service', ['name' => 'sonarr'], []],
        ]))
        ->and($drawn)->toContain(__('stacks.copy.taking', ['scope' => aLineOfTheCopy('stacks.copy.scope.service', ['name' => 'sonarr'])]))
        ->and($drawn)->toContain(__('stacks.copy.no_progress_while_running'))
        ->and($drawn)->toContain(__('health.every.while_work_runs', ['count' => 5]))
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
        ->and($drawn)->not->toContain(__('stacks.copy.a_rehearsal'))
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
        ->and($drawn)->toContain(__('stacks.copy.a_rehearsal'))
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
    $screen = aCopyAgreedTo(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::ended()));
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
    $copying = AStackThatTakesCopies::met(Obstacle::StackDidNotAnswer);
    $screen = aCopyAgreedTo($copying);

    expect(everythingAboutTheCopy($screen->lastCopy()))->toBe(nothingReportedOfTheCopy(['cameBack' => false, 'met' => Obstacle::StackDidNotAnswer->said()]))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));

    $screen->again();

    expect($screen->lastCopy()->wasAsked)->toBeFalse()
        ->and($copying->followed())->toBe([]);
});

it('lets go of a session the stack refused, while taking or while asking after', function (bool $whileAsking): void {
    $keychain = AKeychainInMemory::working();
    $copying = $whileAsking
        ? AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::met(Obstacle::CredentialWasRefused))
        : AStackThatTakesCopies::met(Obstacle::CredentialWasRefused);
    $screen = aCopyAgreedTo($copying, $keychain);

    if ($whileAsking) {
        $screen->whileItRuns();
    }

    expect($screen->lastCopy()->went->isSignedIn)->toBeFalse()
        ->and($screen->lastCopy()->wasAsked)->toBeTrue()
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

    expect($screen->lastCopy()->went->isSignedIn)->toBeFalse();
});

it('offers nothing to copy where the services could not be listed, and says what stood in the way', function (): void {
    $screen = theCopyingScreen(AStackThatTakesCopies::whichTook(HowTheCopyIsGoing::stillRunning()), AStackThatSupervises::met(Obstacle::StackDidNotAnswer));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->offers())->not->toContain(__('stacks.copy.the_whole_stack'))
        ->and($drawn->said())->toContain(__(Obstacle::StackDidNotAnswer->said()));

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
