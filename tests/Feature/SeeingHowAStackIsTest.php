<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;

// N1-R2 — an operator away from the machine can see whether their stack is
// doing what it should.
//
// The thing the whole application is for, and the first screen that does it.
// Pairing and signing in are both means to this end; until this existed the app
// could get in and had nothing to show.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application — `view()` and `__()` are not there in a
// module suite.

/** The machine this screen is about. */
function theStackBeingLookedAt(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** A run that found one thing worth saying. */
function aRunWithAWarning(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        ),
    ));
}

/** A run that found something, and said what it meant and what to try. */
function aRunThatExplainsItself(): Report
{
    return Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.egress-match'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            WhatTheCheckSaid::wentWrong(
                Code::of('VPN-3'),
                'Your address was visible to the swarm',
                Remedies::of(
                    Remedy::of('Restart the tunnel'),
                    Remedy::of('Check the provider is up'),
                ),
                Severity::Critical,
                Standing::Remediable,
            ),
        ),
    ));
}

/**
 * The screen, with a stack it knows and a keychain holding whatever a test says.
 *
 * Named for this file, since the root suites share one namespace (`G10`).
 */
function theHealthScreen(
    AStackThatWasAsked $asking,
    ?AKeychainInMemory $keychain = null,
    ?string $named = null,
): HowThisStackIs {
    $stack = theStackBeingLookedAt();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));

    $screen = new HowThisStackIs($asking, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $named ?? $stack->id()->stored()]);

    return $screen;
}

it('N1-R2 — shows what the checks found, and what it amounts to', function (): void {
    // Both halves, because the top one is not derived from the bottom: a screen
    // working the word out from the findings would be a second opinion about a
    // judgement the engine already made.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->overall())->toBe(Overall::Degraded->saidOnTheScreen())
        ->and($screen->howMany())->toBe(1)
        // Neither of the obstacle's two keys, because nothing was met. The
        // template branches on these being empty, so a word here would put a
        // refusal above a report that arrived.
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('');
});

it('N2-R3 — says what the check meant and what to try, in the core\'s own words', function (): void {
    // The half that was on the wire and going nowhere. `Finding::said()` has
    // carried a meaning, a code and a list of remedies since the translation
    // was written, and until this screen existed no caller asked — an operator
    // saw "The disk is nearly full / Needs attention" and not what that meant
    // for them or what to do about it.
    //
    // Rendered rather than translated: these are the machine's sentences about
    // the machine, and putting them through the catalogue would mean this app
    // inventing a line for a check it has never heard of.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunThatExplainsItself()));

    $rows = $screen->findings();

    expect($rows)->toHaveCount(1);

    $row = $rows[0];

    expect($row->title)->toBe('Torrent traffic leaves through the tunnel')
        ->and($row->about)->toBe(Category::Vpn->saidOnTheScreen())
        ->and($row->explainsItself())->toBeTrue()
        ->and($row->meaning)->toBe('Your address was visible to the swarm')
        ->and($row->code)->toBe('VPN-3')
        ->and($row->remedies->count())->toBe(2);
});

it('N2-R3 — offers every remedy, because the first one may not work', function (): void {
    // `Remedies::likeliest()` exists for a screen with room for one line. This
    // screen has room for the list, and an operator whose first remedy did not
    // work would otherwise have nowhere to find the second.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunThatExplainsItself()));

    $actions = array_map(
        static fn(Remedy $remedy): string => $remedy->action(),
        iterator_to_array($screen->findings()[0]->remedies, preserve_keys: false),
    );

    expect($actions)->toBe(['Restart the tunnel', 'Check the provider is up']);
});

it('N2-R3 — says so where the machine knows what is wrong and has nothing to suggest', function (): void {
    // A failure with no remedy is representable and is a sentence rather than
    // blank space: the operator is being told the stack knows what is wrong and
    // has nothing to offer, which is what sends them to the machine itself.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.egress-match'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            WhatTheCheckSaid::wentWrong(
                Code::of('VPN-3'),
                'Your address was visible to the swarm',
                Remedies::none(),
                Severity::Critical,
                Standing::Remediable,
            ),
        ),
    ))));

    $row = $screen->findings()[0];

    expect($row->explainsItself())->toBeTrue()
        ->and($row->remedies->count())->toBe(0)
        ->and(__('health.nothing_to_try'))->not->toBe('health.nothing_to_try');
});

it('says nothing it was not told about a check that passed', function (): void {
    // A passing check has no meaning to explain and no remedy to offer.
    // Inventing a sentence for one would be this app writing words the machine
    // did not say, which is the opposite of what `N2-R3` asks for.
    $row = $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()))->findings()[0];

    expect($row->explainsItself())->toBeFalse()
        ->and($row->meaning)->toBe('')
        ->and($row->code)->toBe('')
        ->and($row->remedies->count())->toBe(0)
        ->and($row->title)->not->toBe('');
});

it('says which part of the machine every finding is about', function (): void {
    // Carried on every row, wrong or not: a report in the engine's own order is
    // a list an operator scans for the part they are worried about. The order
    // is deliberately not changed to group them — reordering would be this app
    // second-guessing the engine about which finding matters most — so saying
    // what each is about does that work without taking the decision.
    $passing = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()))->findings()[0];

    expect($passing->about)->toBe(Category::Storage->saidOnTheScreen())
        ->and(__($passing->about))->not->toBe($passing->about);

    // And every category resolves, because a report may carry any of them.
    foreach (Category::cases() as $category) {
        expect(__($category->saidOnTheScreen()))
            ->not->toBe($category->saidOnTheScreen(), $category->value);
    }
});

it('N1-R17 — asks once however many times the frame reads it', function (): void {
    // A screen is not a poller. Every accessor reads what one asking produced,
    // and a screen that asked per accessor would open six connections to a
    // machine over somebody's home network to draw one frame.
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());
    $screen = theHealthScreen($asking);

    $screen->overall();
    $screen->findings();
    $screen->met();
    $screen->remedy();
    $screen->isSignedIn();

    expect($asking->askings())->toBe(1)
        ->and($asking->askedAbout())->toBe($screen->stack())
        ->and($asking->wasGivenASession())->toBeTrue();
});

it('N2-R1 — asks again when the operator asks it to, and not otherwise', function (): void {
    // Somebody who has just gone and restarted a service wants to know whether
    // it took. A screen that could only be re-asked by leaving it and coming
    // back teaches them to distrust what it says — and one that asked on a
    // timer would be talking to a machine over a home network unprompted, which
    // is what `N1-R17` refuses. A tap is a prompt.
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());
    $screen = theHealthScreen($asking);

    $screen->overall();
    $screen->findings();

    expect($asking->askings())->toBe(1);

    $screen->again();
    $screen->overall();

    expect($asking->askings())->toBe(2);
});

it('N1-R44 — asking again notices a session that has ended underneath them', function (): void {
    // An operator may have been on this screen a while. A refresh that reused a
    // session it never re-checked would show them a stale report under a stack
    // they are no longer signed into.
    $stack = theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'));
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());

    $screen = new HowThisStackIs($asking, $keychain, StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    expect($screen->isSignedIn())->toBeTrue();

    $keychain->forget($stack->id());
    $screen->again();

    expect($screen->isSignedIn())->toBeFalse()
        ->and($asking->askings())->toBe(1);
});

it('N1-R10 — says what the operator met where the stack did not answer', function (): void {
    // Every obstacle, because the screen shows whichever it was and the keys
    // are derived — so a seventh case needs no edit on this screen and must not
    // arrive without a catalogue line.
    foreach (Obstacle::cases() as $why) {
        $screen = theHealthScreen(AStackThatWasAsked::met($why));

        expect($screen->met())->toBe(sprintf('connection.%s', $why->value), $why->value)
            ->and($screen->remedy())->toBe(sprintf('connection.%s_action', $why->value), $why->value)
            ->and($screen->overall())->toBe('', $why->value)
            // Still signed in. An obstacle is the stack not answering, not this
            // device losing its session — and a screen that read the two as one
            // would send an operator to sign in again over a machine that is
            // merely switched off.
            ->and($screen->isSignedIn())->toBeTrue($why->value)
            ->and($screen->howMany())->toBe(0, $why->value)
            ->and(__($screen->met()))->not->toBe($screen->met(), $why->value)
            ->and(__($screen->remedy()))->not->toBe($screen->remedy(), $why->value);
    }
});

it('N1-R44 — a session that has ended sends them to sign in rather than to an error', function (): void {
    // Nothing was asked, so there is nothing to report and no obstacle to name:
    // the app did not get as far as the machine. The remedy is a screen rather
    // than a sentence, which is why signed-out is its own state.
    $asking = AStackThatWasAsked::saying(aRunWithAWarning());
    $stack = theStackBeingLookedAt();

    $screen = new HowThisStackIs($asking, AKeychainInMemory::working(), StacksInMemory::holding($stack));
    $screen->setParams(['stack' => $stack->id()->stored()]);

    expect($screen->isSignedIn())->toBeFalse()
        ->and($screen->met())->toBe('')
        ->and($screen->remedy())->toBe('')
        ->and($screen->overall())->toBe('')
        ->and($asking->askings())->toBe(0);
});

it('N4-R6 — a keychain that will not open asks for the password rather than breaking', function (): void {
    // The same call `YourStacks` makes: a store that cannot be read is a store
    // with no session in it as far as this question goes, and the honest answer
    // is the sign-in screen.
    foreach ([
        'no store at all' => AKeychainInMemory::withNowhereSafe(),
        'a store that will not open' => AKeychainInMemory::thatWillNotOpen(),
    ] as $which => $keychain) {
        $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()), $keychain);

        expect($screen->isSignedIn())->toBeFalse($which);
    }
});

it('N1-R11 — signing in again goes to this stack and no other', function (): void {
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->signInAt())
        ->toBe(sprintf('/stacks/%s/sign-in', theStackBeingLookedAt()->id()->stored()));
});

it('refuses a route naming a stack this device does not hold', function (): void {
    // A launch-time fault rather than a screen state: the URI names something
    // that has been forgotten, and there is no screen to draw for it.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()), named: 'a-stack-long-forgotten');

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsNotConfigured::class);
});

it('renders the frame it is named for', function (): void {
    expect(theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()))->render()->name())
        ->toBe('operator::how-this-stack-is');
});

it('says something real about every verdict a finding can carry', function (): void {
    // The list under the headline shows one line per finding, and the line is
    // the verdict's own word. A case with no catalogue line would show the key.
    foreach (Conclusion::cases() as $conclusion) {
        expect(__($conclusion->saidOnTheScreen()))
            ->not->toBe($conclusion->saidOnTheScreen(), $conclusion->value);
    }
});

it('refuses a route parameter that is not text', function (): void {
    // A parameter arrives as `mixed`, because the navigation stack's own
    // parameter array is untyped. Anything that is not a string names no stack,
    // which is the same situation as a route with nothing in that segment —
    // asserted rather than assumed, because the narrowing is a branch and a
    // branch nothing drives is a branch that can quietly become the other one.
    // `SigningIntoAStackTest` makes the same assertion about the same shape one
    // screen over.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));
    $screen->setParams(['stack' => 42]);

    expect(fn(): Stack => $screen->stack())->toThrow(StackIsUnidentified::class);
});

it('N2-R3 — a row says what it costs, beside what the verdict was', function (): void {
    // The two are not the same question, and a screen showing only the verdict
    // makes a critical failure and an ordinary one look identical. `WorstFirst`
    // already puts the costlier one higher; this is the row saying why.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunThatExplainsItself()));

    expect($screen->findings()[0]->cost)->toBe(Severity::Critical->saidOnTheScreen())
        ->and(__($screen->findings()[0]->cost))->not->toBe($screen->findings()[0]->cost);
});

it('N2-R3 — a row with nothing graded carries no cost at all', function (): void {
    // A passing check was never graded, so there is no word for how much it
    // costs and none is invented. The template branches on the empty key.
    $screen = theHealthScreen(AStackThatWasAsked::saying(
        Report::of(Overall::Healthy, Findings::of(
            Finding::of(
                Check::of('storage.room'),
                Category::Storage,
                'Room to grow',
                Conclusion::Passed,
                WhatTheCheckSaid::nothingWrong(),
            ),
        )),
    ));

    expect($screen->findings()[0]->cost)->toBe('')
        ->and($screen->findings()[0]->code)->toBe('');
});
