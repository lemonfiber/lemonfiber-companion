<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMonthlyCap;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remark;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatACapDoes;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhereTheLineStands;
use Modules\Kernel\Api\WhereTheMonthStands;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowTheLineIsSharedHere;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatRationsItsLine;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;

// How this machine shares its line with the household.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The moment the measurement's age is counted from. */
const THE_LINE_IS_READ_AT = 1_790_150_000;

/** The machine whose line this screen is about. */
function theStackWhoseLineIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A line with nothing optional known about it. */
function aBareLine(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::Unlimited, 'Nothing holds the stack back', 'No limit', 'No limit', Remarks::of(), Remarks::of());
}

/** A line measured two hours before it was read, capped, with everything said. */
function aCappedLine(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::CapWarning, 'Close to the month\'s cap', 'Half of 100 Mbit/s', 'No limit', Remarks::of('Measured at night'), Remarks::of('Plex streams'))
        ->measuredAt(WhatTheLineCarries::measured(12_500_000, 2_500_000, HowTheLineWasMeasured::Declared, Instant::atEpochSeconds(THE_LINE_IS_READ_AT - 7_200), WhetherItGoesThroughTheTunnel::Through))
        ->cappedAt(AMonthlyCap::of(1_000_000_000_000, WhatACapDoes::Pause)->standing(WhereTheMonthStands::Warning))
        ->withASpentCapDoing(Remark::said('Nothing is being held back yet', 'acting'))
        ->withUploadCosting(Remark::said('Seeding back at a quarter slows the ratio', 'ratio'));
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theLineScreen(
    AStackThatRationsItsLine $rationing,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): HowTheLineIsSharedHere {
    $stack = theStackWhoseLineIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowTheLineIsSharedHere($rationing, $keychain, StacksInMemory::holding($stack), FrozenClock::at(Instant::atEpochSeconds(THE_LINE_IS_READ_AT)));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('shows where the line stands, what it means and each direction in the stack\'s sentence', function (): void {
    $answer = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->standsSaid)->toBe(WhereTheLineStands::CapWarning->saidOnTheScreen())
        ->and($answer->means)->toBe('Close to the month\'s cap')
        ->and($answer->downSays)->toBe('Half of 100 Mbit/s')
        ->and($answer->upSays)->toBe('No limit')
        ->and($answer->cautions)->toBe(['Measured at night'])
        ->and($answer->untouched)->toBe(['Plex streams'])
        ->and($answer->spentCap)->toBe('Nothing is being held back yet')
        ->and($answer->uploadCost)->toBe('Seeding back at a quarter slows the ratio');
});

it('N10-R4, N10-R5 — the capacity says down and up, declared or observed, which path, and how long ago', function (): void {
    $measured = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()))->answer()->measured;

    expect($measured)->not->toBeNull()
        ->and([$measured?->downFigure, $measured?->upFigure])->toBe([13, 3])
        ->and($measured?->measuredSaid)->toBe(HowTheLineWasMeasured::Declared->saidOnTheScreen())
        ->and($measured?->tunnelSaid)->toBe(WhetherItGoesThroughTheTunnel::Through->saidOnTheScreen())
        ->and($measured?->agoSaid)->toBe(HowLongAgo::Hours->saidOnTheScreen())
        ->and($measured?->agoCount)->toBe(2);
});

it('N10-R6 — the cap says its allowance, what reaching it does, and where the month stands', function (): void {
    $cap = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()))->answer()->cap;

    expect($cap)->not->toBeNull()
        ->and([$cap?->figure, $cap?->doesSaid, $cap?->standingSaid])->toBe([1, WhatACapDoes::Pause->saidOnTheScreen(), WhereTheMonthStands::Warning->saidOnTheScreen()]);
});

it('N10-R7 — no cap declared is not a cap of nought, and a line nobody measured is not a line of nothing', function (): void {
    $bare = theLineScreen(AStackThatRationsItsLine::with(aBareLine()))->answer();
    $zero = theLineScreen(AStackThatRationsItsLine::with(aBareLine()->cappedAt(AMonthlyCap::of(0, WhatACapDoes::Continue))))->answer();

    expect($bare->cap)->toBeNull()
        ->and($bare->measured)->toBeNull()
        ->and($bare->spentCap)->toBe('')
        ->and($bare->uploadCost)->toBe('')
        ->and($zero->cap?->figure)->toBe(0)
        ->and($zero->cap?->standingSaid)->toBe('');
});

it('N10-R12 — a stack that could not be asked is not a line with nothing on it', function (): void {
    $answer = theLineScreen(AStackThatRationsItsLine::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->standsSaid)->toBe('')
        ->and($answer->means)->toBe('')
        ->and($answer->downSays)->toBe('')
        ->and($answer->upSays)->toBe('')
        ->and($answer->cautions)->toBe([])
        ->and($answer->untouched)->toBe([])
        ->and($answer->measured)->toBeNull()
        ->and($answer->cap)->toBeNull()
        ->and($answer->spentCap)->toBe('')
        ->and($answer->uploadCost)->toBe('');
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    $answer = theLineScreen(AStackThatRationsItsLine::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R44 — a session that has ended is not a line with nothing on it', function (): void {
    $answer = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()), signedIn: false)->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->standsSaid)->toBe('')
        ->and($answer->measured)->toBeNull()
        ->and($answer->cap)->toBeNull();
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theLineScreen(AStackThatRationsItsLine::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseLineIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseLineIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    $rationing = AStackThatRationsItsLine::with(aCappedLine());
    $screen = theLineScreen($rationing);

    $screen->answer();
    $screen->answer();

    expect($rationing->askings())->toBe(1)
        ->and($rationing->wasGivenASession())->toBeTrue()
        ->and($rationing->askedAbout()?->id()->stored())->toBe(theStackWhoseLineIsRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $rationing = AStackThatRationsItsLine::met(Obstacle::DeviceHasNoNetwork);
    $screen = theLineScreen($rationing);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($rationing->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theLineScreen(AStackThatRationsItsLine::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theLineScreen(AStackThatRationsItsLine::with(aCappedLine()));

    expect($screen->render()->name())->toBe('operator::how-the-line-is-shared-here');
});
