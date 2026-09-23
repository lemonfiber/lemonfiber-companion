<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereItStopsShort;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowTheRecordReads;
use Modules\Operator\Internal\Screens\WhatWasChangedHere;
use Modules\Operator\Internal\ViewModels\WhatOneRecordedChangeSays;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatKeepsARecord;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;

// What this machine has changed about itself, and how far each change goes back.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The moment every age on these screens is measured against. */
const THE_RECORD_IS_READ_AT = 1_790_150_000;

/** The machine whose record this screen is about. */
function theStackWhoseRecordIsRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A change made this many seconds before the record was read. */
function aChangeMadeBefore(string $did, int $secondsBefore, HowFarItGoesBack $reversal = HowFarItGoesBack::Whole, int $alongside = 1): Change
{
    return Change::made(
        $did,
        'reconfigure',
        'sonarr',
        WhenItWasMade::at(Instant::atEpochSeconds(THE_RECORD_IS_READ_AT - $secondsBefore)),
        $reversal,
        $alongside,
    );
}

/** A change the stack's clock could not date. */
function aChangeNobodyDated(string $did): Change
{
    return Change::made($did, 'seed', 'lemonfiber', WhenItWasMade::unreadable(), HowFarItGoesBack::None, 1);
}

/** The screen, with a stack it knows and a keychain holding whatever a test says. Named for this file (`G10`). */
function theRecordScreen(
    AStackThatKeepsARecord $history,
    ?AKeychainInMemory $keychain = null,
    bool $signedIn = true,
): WhatWasChangedHere {
    $stack = theStackWhoseRecordIsRead();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatWasChangedHere(
        $history,
        $keychain,
        StacksInMemory::holding($stack),
        FrozenClock::at(Instant::atEpochSeconds(THE_RECORD_IS_READ_AT)),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N11-R1 — says how far back the record goes', function (): void {
    $answer = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs of lemonfiber\'s own changes',
        aChangeMadeBefore('Pointed Sonarr at the new library', 120),
    )))->answer();

    expect($answer->went->cameBack())->toBeTrue()
        ->and($answer->went->isSignedIn)->toBeTrue()
        ->and($answer->horizon)->toBe('the last 50 runs of lemonfiber\'s own changes');
});

it('N11-R1 — still says how far back it goes when nothing is under it', function (): void {
    // An empty record without its horizon reads as *nothing has ever happened
    // here*, which is the one reading the horizon exists to prevent.
    $answer = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching('the last 50 runs')))->answer();

    expect($answer->moments)->toBe([])
        ->and($answer->horizon)->toBe('the last 50 runs')
        ->and($answer->went->cameBack())->toBeTrue();
});

it('N11-R2, N11-R3 — every change says what it did, how far it goes back and how many came with it', function (): void {
    $answer = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeMadeBefore('Pointed Sonarr at the new library', 120, HowFarItGoesBack::Partial, 3)
            ->stoppingShort(WhereItStopsShort::suggesting('The old library was deleted', 'Restore it from the last backup first')),
    )))->answer();

    $row = $answer->moments[0]->changes[0];

    expect($row->did)->toBe('Pointed Sonarr at the new library')
        ->and($row->operation)->toBe('reconfigure')
        ->and($row->target)->toBe('sonarr')
        ->and($row->reversalSaid)->toBe(HowFarItGoesBack::Partial->saidOnTheScreen())
        ->and($row->alongside)->toBe(3)
        ->and($row->because)->toBe('The old library was deleted')
        ->and($row->instead)->toBe('Restore it from the last backup first');
});

it('N11-R2 — a change that goes back whole says nothing about stopping short', function (): void {
    // Empty, which is what the template branches on. A heading with nothing
    // under it would read as a limit nobody knows the reason for.
    $row = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeMadeBefore('Set up the download client', 120),
    )))->answer()->moments[0]->changes[0];

    expect($row->because)->toBe('')->and($row->instead)->toBe('');
});

it('N11-R2 — a reason with nothing to suggest carries the reason alone', function (): void {
    $row = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeMadeBefore('Removed the old share', 120, HowFarItGoesBack::None)
            ->stoppingShort(WhereItStopsShort::because('The files are gone')),
    )))->answer()->moments[0]->changes[0];

    expect($row->because)->toBe('The files are gone')->and($row->instead)->toBe('');
});

it('says when as an age, measured against the moment the record was read', function (): void {
    $moment = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeMadeBefore('Pointed Sonarr at the new library', 2 * 3_600 + 60),
    )))->answer()->moments[0];

    expect($moment->whenSaid)->toBe(HowLongAgo::Hours->saidOnTheScreen())
        ->and($moment->whenCount)->toBe(2);
});

it('N11-R10 — changes made at one moment are drawn under it together, in the stack\'s order', function (): void {
    // Not one before the other: listed each with a time, the one above would
    // read as the later.
    $moments = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeMadeBefore('Third', 120),
        aChangeMadeBefore('Second', 120),
        aChangeMadeBefore('First', 7_200),
    )))->answer()->moments;

    expect($moments)->toHaveCount(2)
        ->and(array_map(static fn(WhatOneRecordedChangeSays $row): string => $row->did, $moments[0]->changes))->toBe(['Third', 'Second'])
        ->and(array_map(static fn(WhatOneRecordedChangeSays $row): string => $row->did, $moments[1]->changes))->toBe(['First']);
});

it('N11-R10 — changes the clock could not date are never drawn as one moment', function (): void {
    // Nobody knows they happened together, so they are not drawn together, and
    // neither is drawn as a date.
    $moments = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching(
        'the last 50 runs',
        aChangeNobodyDated('Wrote the first configuration'),
        aChangeNobodyDated('Made the data directory'),
    )))->answer()->moments;

    expect($moments)->toHaveCount(2)
        ->and($moments[0]->whenSaid)->toBe(HowTheRecordReads::CLOCK_UNREADABLE)
        ->and($moments[0]->whenCount)->toBe(0)
        ->and($moments[1]->whenSaid)->toBe(HowTheRecordReads::CLOCK_UNREADABLE);
});

it('N11-R9 — a stack that could not be asked is not a record of nothing', function (): void {
    // Both would draw an empty list, and only one of them means nothing
    // happened.
    $answer = theRecordScreen(AStackThatKeepsARecord::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->cameBack())->toBeFalse()
        ->and($answer->went->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($answer->moments)->toBe([])
        ->and($answer->horizon)->toBe('');
});

it('N1-R3 — an obstacle that is not a refused credential leaves the session standing', function (): void {
    // A phone with no signal told to sign in is given advice for a problem it
    // does not have, over the one it does.
    $answer = theRecordScreen(AStackThatKeepsARecord::met(Obstacle::StackDidNotAnswer))->answer();

    expect($answer->went->isSignedIn)->toBeTrue();
});

it('N1-R44 — a session that has ended is not a machine that changed nothing', function (): void {
    $answer = theRecordScreen(
        AStackThatKeepsARecord::with(TheRecord::reaching('the last 50 runs')),
        signedIn: false,
    )->answer();

    expect($answer->went->isSignedIn)->toBeFalse()
        ->and($answer->went->met)->toBe('')
        ->and($answer->moments)->toBe([])
        ->and($answer->horizon)->toBe('');
});

it('N3-R13 — a credential the stack refused signs this device out and lets the session go', function (): void {
    // Both halves: if the session stayed, the next frame would resume it, be
    // refused again, and draw a sign-in prompt over a device that still
    // believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theRecordScreen(AStackThatKeepsARecord::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackWhoseRecordIsRead()->id()))->toBeTrue();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($keychain->isHolding(theStackWhoseRecordIsRead()->id()))->toBeFalse();
});

it('the machine is asked once for a frame, about the machine the route names', function (): void {
    // Every accessor is a template expression, and the answers of a screen
    // that asked on each would all agree — so the count is what is read.
    $history = AStackThatKeepsARecord::with(TheRecord::reaching('the last 50 runs'));
    $screen = theRecordScreen($history);

    $screen->answer();
    $screen->answer();

    expect($history->askings())->toBe(1)
        ->and($history->wasGivenASession())->toBeTrue()
        ->and($history->askedAbout()?->id()->stored())->toBe(theStackWhoseRecordIsRead()->id()->stored());
});

it('N1-R3 — asking again asks the machine again', function (): void {
    $history = AStackThatKeepsARecord::met(Obstacle::DeviceHasNoNetwork);
    $screen = theRecordScreen($history);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($history->askings())->toBe(2);
});

it('refuses a route parameter that is not text', function (): void {
    $screen = theRecordScreen(AStackThatKeepsARecord::met(Obstacle::DeviceHasNoNetwork));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('the way back to the machine is a route as well', function (): void {
    $screen = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching('the last 50 runs')));

    expect(NativeRouter::resolve($screen->goes()->health()))->not->toBeNull();
});

it('renders its own view', function (): void {
    $screen = theRecordScreen(AStackThatKeepsARecord::with(TheRecord::reaching('the last 50 runs')));

    expect($screen->render()->name())->toBe('operator::what-was-changed-here');
});
