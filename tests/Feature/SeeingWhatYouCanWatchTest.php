<?php

declare(strict_types=1);

use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

// What a member already has, as against what they can ask for.
//
// The screen renders the core's answer and nothing else: no row is filtered,
// sorted or hidden here, and the one thing it must get right beyond that is
// telling an empty shelf from a library it could not reach. Those are drawn by
// the same loop and say opposite things to the person reading them.
//
// Here rather than in the household module's own tests because a screen
// renders, and rendering needs the application — `view()` and `__()` are not
// there in a module suite.

/** The machine a member's shelf is read from. */
function theStackAShelfIsReadFrom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** A shelf with one of each kind on it, the middle one undated. */
function aShelfOfThree(): Shelf
{
    return Shelf::of(
        Holding::of(HoldingId::called('a1'), 'A film', Medium::Film, WhenItCameOut::in(1999)),
        Holding::of(HoldingId::called('b2'), 'A series', Medium::Series, WhenItCameOut::unstated()),
        Holding::of(HoldingId::called('c3'), 'Something else', Medium::Other, WhenItCameOut::in(2012)),
    );
}

/** The shelf screen, over a fake, with a session unless a test says otherwise. */
function theShelfScreen(
    AShelfThatWasRead $watching,
    bool $signedIn = true,
    ?Whose $whose = null,
): WhatYouCanWatch {
    $stack = theStackAShelfIsReadFrom();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep(
            $stack->id(),
            Session::of('a-session-not-a-secret'),
            $whose ?? Whose::member('ada'),
        );
    }

    $screen = new WhatYouCanWatch($watching, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('N3-R14 — draws the shelf the core listed, in its order and unfiltered', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    $titles = array_map(
        static fn(object $row): string => $row->titled,
        $screen->answer()->holdings,
    );

    expect($titles)->toBe(['A film', 'A series', 'Something else'])
        ->and($screen->answer()->cameBack())->toBeTrue();
});

it('N3-R14 — names each kind against a key rather than in English', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));

    $media = array_map(
        static fn(object $row): string => $row->medium,
        $screen->answer()->holdings,
    );

    expect($media)->toBe([
        'household.medium.film',
        'household.medium.series',
        'household.medium.other',
    ]);
});

it('leaves the year off a holding the core could not date', function (): void {
    // Empty rather than a placeholder: a year invented here would be a fact
    // about somebody's library that nobody claimed.
    $years = array_map(
        static fn(object $row): string => $row->year,
        theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()))->answer()->holdings,
    );

    expect($years)->toBe(['1999', '', '2012']);
});

it('N3-R15 — draws a library out of reach as itself, never as an empty shelf', function (): void {
    // The failure this screen is written around. Both are no rows, and one
    // says *you have nothing* while the other says *your collection could not
    // be reached* — and the first, said wrongly, tells somebody their library
    // is gone.
    $outOfReach = theShelfScreen(AShelfThatWasRead::outOfReach(
        Sentences::of(Sentence::of('The media server did not answer.')),
    ));

    expect($outOfReach->answer()->isOutOfReach)->toBeTrue()
        ->and($outOfReach->answer()->cameBack())->toBeFalse()
        ->and($outOfReach->answer()->reasons)->toBe(['The media server did not answer.']);

    $drawn = WhatTheDeviceWouldDraw::by($outOfReach)->said();

    expect($drawn)->toContain(__('household.shelf_is_out_of_reach'));
    expect($drawn)->toContain('The media server did not answer.');
    expect($drawn)->not->toContain(__('household.shelf_is_empty'));
});

it('says an empty shelf in as many words', function (): void {
    $empty = theShelfScreen(AShelfThatWasRead::holdingNothing());

    expect($empty->answer()->cameBack())->toBeTrue()
        ->and($empty->answer()->holdings)->toBe([]);

    $drawn = WhatTheDeviceWouldDraw::by($empty)->said();

    expect($drawn)->toContain(__('household.shelf_is_empty'));
    expect($drawn)->not->toContain(__('household.shelf_is_out_of_reach'));
});

it('N1-R10 — reports what stood in the way and keeps the way back', function (): void {
    $met = theShelfScreen(AShelfThatWasRead::met(Obstacle::StackDidNotAnswer));

    expect($met->answer()->met)->toBe(Obstacle::StackDidNotAnswer->said())
        ->and($met->answer()->remedy)->toBe(Obstacle::StackDidNotAnswer->remedy())
        ->and($met->answer()->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($met)->offers())->not->toBe([]);
});

it('N3-R13 — a refused credential is a signed-out device, not a report', function (): void {
    $refused = theShelfScreen(AShelfThatWasRead::met(Obstacle::CredentialWasRefused));

    expect($refused->answer()->isSignedIn)->toBeFalse()
        ->and($refused->answer()->cameBack())->toBeFalse();
});

it('draws the way back in where this device holds no session', function (): void {
    $out = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()), signedIn: false);

    expect($out->answer()->isSignedIn)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($out)->said())->toContain(__('connection.session_has_ended'));
});

it('N1-R65 — asks the machine once per frame, however many fields are read', function (): void {
    $watching = AShelfThatWasRead::holding(aShelfOfThree());
    $screen = theShelfScreen($watching);

    $screen->answer();
    $screen->answer();
    $screen->answer();

    expect($watching->askings())->toBe(1)
        ->and($watching->askedAbout()?->id()->stored())
        ->toBe(theStackAShelfIsReadFrom()->id()->stored());
});

it('N1-R3 — asking again is offered, and asks again', function (): void {
    $watching = AShelfThatWasRead::holding(aShelfOfThree());
    $screen = theShelfScreen($watching);

    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($watching->askings())->toBe(2);
});

it('offers the way back to the machine and to a renewed session', function (): void {
    $screen = theShelfScreen(AShelfThatWasRead::holding(aShelfOfThree()));
    $stack = theStackAShelfIsReadFrom()->id();

    expect($screen->health())->toBe(AStacksScreen::Health->forTheStack($stack))
        ->and($screen->signIn())->toBe(AStacksScreen::SignIn->forTheStack($stack));
});

it('is what the router serves under the shelf path', function (): void {
    $resolved = NativeRouter::resolve(
        AStacksScreen::Shelf->forTheStack(theStackAShelfIsReadFrom()->id()),
    );

    expect($resolved['class'] ?? null)->toBe(WhatYouCanWatch::class);
});
