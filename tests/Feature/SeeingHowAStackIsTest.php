<?php

declare(strict_types=1);

use Modules\Kernel\Api\AboutWhat;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Because;
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
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;
use Modules\Operator\Internal\ViewModels\WhichFamilyToRead;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

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
                WhatItSaysUnderneath::none(),
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

    expect($screen->answer()->overall)->toBe(Overall::Degraded->saidOnTheScreen())
        ->and($screen->howMany())->toBe(1)
        // Neither of the obstacle's two keys, because nothing was met. The
        // template branches on these being empty, so a word here would put a
        // refusal above a report that arrived.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('');
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
                WhatItSaysUnderneath::none(),
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

    $screen->answer();
    $screen->findings();
    $screen->answer();
    $screen->answer();
    $screen->answer();

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

    $screen->answer();
    $screen->findings();

    expect($asking->askings())->toBe(1);

    $screen->again();
    $screen->answer();

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

    expect($screen->answer()->went->isSignedIn)->toBeTrue();

    $keychain->forget($stack->id());
    $screen->again();

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($asking->askings())->toBe(1);
});

it('N1-R10 — says what the operator met where the stack did not answer', function (): void {
    // Every obstacle that leaves the session standing, because the screen shows
    // whichever it was and the keys are derived — so a seventh case needs no
    // edit on this screen and must not arrive without a catalogue line.
    //
    // The one that does not leave it standing is asked about on its own below.
    // Asked here off the enum rather than listed, so a case that starts or
    // stops ending a session moves between the two cases by itself.
    $standing = array_values(array_filter(
        Obstacle::cases(),
        static fn(Obstacle $why): bool => ! $why->meansWeAreSignedOut(),
    ));

    expect($standing)->not->toBe([], 'every obstacle now ends a session, which cannot be right');

    foreach ($standing as $why) {
        $screen = theHealthScreen(AStackThatWasAsked::met($why));

        expect($screen->answer()->went->met)->toBe(sprintf('connection.%s', $why->value), $why->value)
            ->and($screen->answer()->went->remedy)->toBe(sprintf('connection.%s_action', $why->value), $why->value)
            ->and($screen->answer()->overall)->toBe('', $why->value)
            // Still signed in. An obstacle is the stack not answering, not this
            // device losing its session — and a screen that read the two as one
            // would send an operator to sign in again over a machine that is
            // merely switched off.
            ->and($screen->answer()->went->isSignedIn)->toBeTrue($why->value)
            ->and($screen->howMany())->toBe(0, $why->value)
            ->and(__($screen->answer()->went->met))->not->toBe($screen->answer()->went->met, $why->value)
            ->and(__($screen->answer()->went->remedy))->not->toBe($screen->answer()->went->remedy, $why->value);
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

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        ->and($screen->answer()->overall)->toBe('')
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

        expect($screen->answer()->went->isSignedIn)->toBeFalse($which);
    }
});

it('N1-R11 — signing in again goes to this stack and no other', function (): void {
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->goes()->signIn())
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

it('N2-R3 — a row whose check could not run carries the reason and nothing else', function (): void {
    // Nothing was graded: a check that could not run produced no judgement, so
    // there is no identifier to quote and no word for what it costs, and a
    // word in either place would be this app inventing one.
    //
    // Driven through `couldNotSay`, which every other test on this screen
    // walks past — they all build a check that passed, and that reaches the
    // row through a different arm. `explainsItself()` cannot stand in for
    // this: it reads the meaning, and on this path the meaning is the reason,
    // so it is true whatever the other two hold.
    $screen = theHealthScreen(AStackThatWasAsked::saying(
        Report::of(Overall::Degraded, Findings::of(
            Finding::of(
                Check::of('vpn.egress-match'),
                Category::Vpn,
                'Torrent traffic leaves through the tunnel',
                Conclusion::Unverified,
                WhatTheCheckSaid::couldNotSay(
                    'The tunnel was down, so where the traffic left could not be established',
                    Remedies::of(Remedy::of('Start the tunnel and run the checks again')),
                ),
            ),
        )),
    ));

    $row = $screen->findings()[0];

    expect($row->code)->toBe('')
        ->and($row->cost)->toBe('')
        // And the row is not blank: the reason and what to try about it both
        // land in the two fields a failure uses, because an operator wants to
        // know what a row means and what to do whichever outcome produced it.
        ->and($row->meaning)->toBe('The tunnel was down, so where the traffic left could not be established')
        ->and($row->explainsItself())->toBeTrue()
        ->and($row->remedies->count())->toBe(1)
        ->and($row->verdict)->toBe(Conclusion::Unverified->saidOnTheScreen());
});

/**
 * The one row a screen draws for a failing check, with whatever detail the core
 * added under it.
 */
function aFindingRowFor(WhatItSaysUnderneath $underneath): WhatOneFindingSays
{
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.egress'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            WhatTheCheckSaid::wentWrong(
                Code::of('VPN-3'),
                'Your address was visible to the swarm',
                Remedies::of(Remedy::of('Restart the tunnel')),
                Severity::Critical,
                Standing::Guided,
                $underneath,
            ),
        ),
    ))));

    return $screen->findings()[0];
}

/** A failing verdict, for the rows that exist to carry an attribution. */
function aFailingVerdict(): WhatTheCheckSaid
{
    return WhatTheCheckSaid::wentWrong(
        Code::of('VPN-3'),
        'Your address was visible to the swarm',
        Remedies::none(),
        Severity::Critical,
        Standing::Guided,
        WhatItSaysUnderneath::none(),
    );
}

it('N2-R3 — a row names the service it is about, and what explains it', function (): void {
    // The cause is shown by the other row's *title*, not by its identifier.
    // `vpn.up` is right on a wire and jargon on a phone; "The tunnel" is what
    // the operator is looking at two rows up.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.up'),
            Category::Vpn,
            'The tunnel',
            Conclusion::Failed,
            aFailingVerdict(),
        )->about(AboutWhat::theService('gluetun')),
        Finding::of(
            Check::of('vpn.egress-match'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            aFailingVerdict(),
        )->about(AboutWhat::theService('qbittorrent'))->because(Because::theCheck(Check::of('vpn.up'))),
    ))));

    $rows = $screen->findings();

    expect($rows[0]->service)->toBe('gluetun')
        ->and($rows[0]->because)->toBe('')
        ->and($rows[1]->service)->toBe('qbittorrent')
        ->and($rows[1]->because)->toBe('The tunnel');
});

it('N2-R3 — a cause the report does not hold is shown as the identifier', function (): void {
    // A report attributing a finding to a check it does not contain has a fault
    // in it. Showing the identifier is honest rather than tidy: the operator
    // has a string they can quote to somebody who can fix it, where a blank
    // would leave them with a row that used to say something.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.egress-match'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            aFailingVerdict(),
        )->because(Because::theCheck(Check::of('a.check.that.did.not.run'))),
    ))));

    expect($screen->findings()[0]->because)->toBe('a.check.that.did.not.run');
});

it('N2-R3 — a row about the machine names no service', function (): void {
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunThatExplainsItself()));

    expect($screen->findings()[0]->service)->toBe('')
        ->and($screen->findings()[0]->because)->toBe('');
});

/** A run whose worst finding is not the one the checks reached first. */
function aRunWhoseWorstRanLast(): Report
{
    return Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('storage.room'),
            Category::Storage,
            'Room to grow',
            Conclusion::Passed,
            WhatTheCheckSaid::nothingWrong(),
        ),
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
                WhatItSaysUnderneath::none(),
            ),
        ),
    ));
}

/** A run with something to say about two families and nothing about the other seven. */
function aRunAcrossTwoFamilies(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('queue.stuck'),
            Category::Queue,
            'Two downloads have not moved',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        ),
        Finding::of(
            Check::of('queue.imports'),
            Category::Queue,
            'An import keeps failing',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        ),
        Finding::of(
            Check::of('storage.room'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
        ),
    ));
}

it('N2-R2 — shows the worst finding first, whatever order the checks ran in', function (): void {
    // The engine sends findings in the order the checks ran, and a list drawn
    // straight off the envelope looks ordered on any report whose worst finding
    // happened to run first. This one deliberately is not that report: a
    // passing storage check arrives ahead of a critical failure, and the screen
    // is what has to put them the right way round.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWhoseWorstRanLast()));

    $titles = array_map(
        static fn(WhatOneFindingSays $row): string => $row->title,
        $screen->findings(),
    );

    expect($titles)->toBe([
        'Torrent traffic leaves through the tunnel',
        'Room to grow',
    ]);
});

it('N2-R9 — offers the families this run has something to say about, and no others', function (): void {
    // Storage before Queue, which is the engine's own order rather than the
    // order the checks ran — the two queue findings arrived first. And seven
    // families are missing, because a control leading to a blank screen teaches
    // an operator that the row is not worth reading.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $offered = array_map(
        static fn(WhichFamilyToRead $family): array => [$family->family, $family->howMany, $family->isOpen],
        $screen->families(),
    );

    expect($offered)->toBe([
        [Category::Storage->value, 1, false],
        [Category::Queue->value, 2, false],
    ]);

    // And each control has a word for itself and a line to show it in, because
    // a control drawn from a key nothing resolves renders the key.
    $storage = $screen->families()[0];

    expect($storage->said)->toBe(Category::Storage->saidOnTheScreen())
        ->and(__($storage->said))->not->toBe($storage->said)
        // Both replacements land, which is the whole of what the line does: a
        // name with no number beside it is a control an operator has to open
        // in order to find out whether it was worth opening.
        ->and(__('health.family_and_count', ['family' => 'Storage', 'count' => $storage->howMany]))
        ->toContain('Storage')
        ->toContain('1');
});

it('N2-R9 — reading one family narrows the report to it', function (): void {
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3);

    $screen->read(Category::Queue->value);

    $about = array_map(
        static fn(WhatOneFindingSays $row): string => $row->about,
        $screen->findings(),
    );

    expect($screen->isNarrowed())->toBeTrue()
        ->and($screen->howMany())->toBe(2)
        ->and($about)->toBe([
            Category::Queue->saidOnTheScreen(),
            Category::Queue->saidOnTheScreen(),
        ]);

    // The row itself does not narrow with the list. Both families keep their
    // control and their count — a row that collapsed to the family being read
    // would take away the only way back to the other one — and the one being
    // read says so, because the template has nothing else to draw it from.
    $offered = array_map(
        static fn(WhichFamilyToRead $family): array => [$family->family, $family->howMany, $family->isOpen],
        $screen->families(),
    );

    expect($offered)->toBe([
        [Category::Storage->value, 1, false],
        [Category::Queue->value, 2, true],
    ]);
});

it('N2-R9 — reading the family already open widens back out', function (): void {
    // The way back is the way in, which is the gesture somebody makes without
    // being told: there is no separate "all" control to go and find.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $screen->read(Category::Queue->value);
    $screen->read(Category::Queue->value);

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3);

    // And the control that was open closes with the report it narrowed. The
    // row is the only thing on the frame that says which family is being read
    // — the list below it looks the same whether nine findings arrived or nine
    // were left after narrowing — so a control still marked open over a report
    // that has widened tells the operator they are reading the queue while
    // they are reading everything. Asserted on the way out and not only on the
    // way in, because the two are separate reads of the same state and only
    // the way in has ever been looked at.
    $closed = array_map(
        static fn(WhichFamilyToRead $family): bool => $family->isOpen,
        $screen->families(),
    );

    expect($closed)->toBe([false, false]);

    // And naming another family while one is open moves to it rather than
    // widening, which is the other half of the same tap.
    $screen->read(Category::Queue->value);
    $screen->read(Category::Storage->value);

    expect($screen->isNarrowed())->toBeTrue()
        ->and($screen->howMany())->toBe(1)
        ->and($screen->findings()[0]->about)->toBe(Category::Storage->saidOnTheScreen());
});

it('N2-R9 — a value naming no family shows the whole report rather than nothing', function (): void {
    // The screen's own state is the only thing that writes it, so this is a
    // value that cannot arrive — and `tryFrom` is what makes that a fact rather
    // than a hope. What it prevents is a blank report where the operator
    // expected a report, which is a worse answer than a tap that does nothing.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $screen->read('a-family-this-engine-has-never-heard-of');

    $open = array_map(
        static fn(WhichFamilyToRead $family): bool => $family->isOpen,
        $screen->families(),
    );

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3)
        ->and($screen->findings())->toHaveCount(3)
        ->and($open)->toBe([false, false]);
});

it('N2-R11 — what the household asked for is one tap from the machine it is about', function (): void {
    // The link's half of the pair. `SeeingWhatTheHouseholdAskedForTest` asserts
    // that something is registered under that route; this asserts that the
    // screen an operator is actually looking at points at it, which is what
    // makes the requests screen reachable rather than merely present.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->goes()->requests())
        ->toBe(AStacksScreen::Requests->forTheStack(theStackBeingLookedAt()->id()->stored()))
        ->and(NativeRouter::resolve($screen->goes()->requests()))->not->toBeNull();
});

it('N2-R4 — what this machine would put right is one tap from the machine', function (): void {
    // The link's half of the pair. `SeeingWhatWouldBePutRightTest` asserts that
    // something is registered under that route; this asserts that the screen an
    // operator is looking at points at it. A screen rather than a button beside
    // one finding, because a listing reached from one finding would show the
    // repairs for all of them under a heading naming one.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->goes()->repairs())
        ->toBe(AStacksScreen::Repairs->forTheStack(theStackBeingLookedAt()->id()->stored()))
        ->and(NativeRouter::resolve($screen->goes()->repairs()))->not->toBeNull();
});

it('N2-R9 — what stopped coming in is one tap from the machine', function (): void {
    // *Reachable* is the whole requirement, and this is the tap that satisfies
    // it. It hangs off the machine rather than off the verdict above, because a
    // stack passing every check and a household getting nothing are not a
    // contradiction — a screen under the verdict would be claiming they are.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    expect($screen->goes()->stuck())
        ->toBe(AStacksScreen::Stuck->forTheStack(theStackBeingLookedAt()->id()->stored()))
        ->and(NativeRouter::resolve($screen->goes()->stuck()))->not->toBeNull();
});

it('N2-R10 — a finding about a service offers what that service said', function (): void {
    // Offered from the finding rather than from a list of every service the
    // stack runs: this row is already about one, and a picker would put a
    // choice in front of somebody who came here to read a specific thing.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.up'),
            Category::Vpn,
            'The tunnel',
            Conclusion::Failed,
            aFailingVerdict(),
        )->about(AboutWhat::theService('gluetun')),
    ))));

    $rows = $screen->findings();

    expect($rows[0]->service)->toBe('gluetun')
        ->and($screen->logsOf($rows[0]->service))
        ->toBe(AStacksScreen::Logs->forTheStacksService(
            theStackBeingLookedAt()->id()->stored(),
            'gluetun',
        ))
        ->and(NativeRouter::resolve($screen->logsOf($rows[0]->service)))->not->toBeNull();
});

it('N2-R10 — a finding about the machine has no log to go to, and asking for one comes away quietly', function (): void {
    // `WhatOneFindingSays::$service` is documented as the service *or empty*, and
    // a finding about the machine is the empty one. The template does not draw
    // the button for those rows — but this is a public method on a screen, and a
    // client can call it with anything it likes. Safety that rests on a template
    // remembering is the shape `N3-R3` refuses one level up.
    //
    // So it answers this machine's own screen: a button leading back to where the
    // operator already is leads nowhere wrong, where building the value object
    // first put a raise on a tap.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk',
            Conclusion::Failed,
            aFailingVerdict(),
        ),
    ))));

    $rows = $screen->findings();

    expect($rows[0]->service)->toBe('')
        ->and($screen->logsOf($rows[0]->service))->toBe($screen->goes()->health())
        ->and(NativeRouter::resolve($screen->logsOf($rows[0]->service)))->not->toBeNull();
});

it('a name that is only whitespace is no name either', function (): void {
    // The trim is the whole of the difference, and a guard comparing the
    // untrimmed string would let every one of these reach `ServiceId::called()`,
    // which raises on exactly the same set.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    foreach ([' ', '   ', "\t", "\n"] as $blank) {
        expect($screen->logsOf($blank))->toBe($screen->goes()->health(), sprintf('logsOf(%s)', var_export($blank, return: true)));
    }
});

it('N3-R13 — a credential the stack refused signs this device out', function (): void {
    // Not one of the six sentences about a machine. The stack answered and said
    // no, so whatever this device is holding is not a session any more — the
    // identity was removed, the password changed, or the stack rebuilt.
    $keychain = AKeychainInMemory::working();
    $screen = theHealthScreen(AStackThatWasAsked::met(Obstacle::CredentialWasRefused), $keychain);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        // Nothing about a machine, because this is not about the machine.
        ->and($screen->answer()->went->met)->toBe('')
        ->and($screen->answer()->went->remedy)->toBe('')
        // And nothing already loaded: `N3-R13` names that half separately.
        ->and($screen->howMany())->toBe(0)
        ->and($screen->answer()->overall)->toBe('');
});

it('N3-R13 — and the session is let go of, not merely hidden', function (): void {
    // The half a fold cannot do. A session left in the store is resumed on the
    // next frame and refused again, so the operator would be looking at a
    // sign-in prompt over a device that still believes it is signed in.
    $keychain = AKeychainInMemory::working();
    $screen = theHealthScreen(AStackThatWasAsked::met(Obstacle::CredentialWasRefused), $keychain);

    expect($keychain->isHolding(theStackBeingLookedAt()->id()))->toBeTrue();

    $screen->answer();

    expect($keychain->isHolding(theStackBeingLookedAt()->id()))->toBeFalse();
});

it('N3-R13 — no other obstacle throws the session away', function (): void {
    // A walk out of wifi must not look like being thrown out of the house. The
    // five that leave a session standing are asked off the enum, so a case that
    // starts ending one moves itself into the case above.
    foreach (Obstacle::cases() as $why) {
        if ($why->meansWeAreSignedOut()) {
            continue;
        }

        $keychain = AKeychainInMemory::working();
        theHealthScreen(AStackThatWasAsked::met($why), $keychain)->answer();

        expect($keychain->isHolding(theStackBeingLookedAt()->id()))->toBeTrue($why->value);
    }
});

it('G4-R4 — the detail is on the row, under everything that leads', function (): void {
    // Available and not leading, which on a surface with one column is a
    // question about order. The plain explanation and what to try come first;
    // this is last, so somebody who wants it has it and everybody else has
    // already read the sentence written for them.
    $row = aFindingRowFor(WhatItSaysUnderneath::said('dial tcp 10.0.0.4:8989: connection refused'));

    expect($row->underneath)->toBe('dial tcp 10.0.0.4:8989: connection refused')
        // The two that lead are still there and still lead.
        ->and($row->meaning)->not->toBe('')
        ->and($row->remedies->count())->toBe(1);
});

it('G4-R4 — a check with no verdict to explain carries no detail either', function (): void {
    // The other two readings of a check, which reach this row by a different
    // arm and were the two the detail was never asserted on. A check that
    // passed has nothing underneath because nothing went wrong; a check that
    // could not run has nothing underneath because it never got far enough to
    // produce one. Both are the empty string, and the template branches on it.
    $screen = theHealthScreen(AStackThatWasAsked::saying(Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('vpn.egress'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Passed,
            WhatTheCheckSaid::nothingWrong(),
        ),
        Finding::of(
            Check::of('vpn.killswitch'),
            Category::Vpn,
            'The tunnel drops traffic when it goes down',
            Conclusion::Unverified,
            WhatTheCheckSaid::couldNotSay(
                'The tunnel was down, so the killswitch could not be exercised',
                Remedies::of(Remedy::of('Bring the tunnel up and ask again')),
            ),
        ),
    ))));

    expect($screen->findings()[0]->underneath)->toBe('')
        ->and($screen->findings()[1]->underneath)->toBe('');
});

it('G4-R4 — a finding the core added nothing to carries no detail', function (): void {
    // The template branches on the empty string, so a row that carried a blank
    // heading would draw *what the check reported* with nothing under it —
    // which reads as the app knowing something and not saying it.
    expect(aFindingRowFor(WhatItSaysUnderneath::none())->underneath)->toBe('');
});

it('N1-R2 — the findings reach the glass, not only the view model', function (): void {
    // Every assertion above this one reads the view model, which is the right
    // shape for asking what the screen decided and cannot answer what it drew.
    // The findings are a list, and a list is drawn by walking a collection
    // inside a component — the one part of a frame that no template rule sees,
    // because `F3`, `F5` and `F10` all read Blade as text and a `@forelse` with
    // nothing in it reads exactly like one with three rows.
    //
    // So the tree is rendered and asked. `F15` draws every screen the router
    // serves and reaches this one behind a session it does not hold, so what it
    // proves is that the frame is drawn at all; this is what proves the reading
    // is on it.
    $screen = theHealthScreen(AStackThatWasAsked::saying(aRunWithAWarning()));

    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__(Overall::Degraded->saidOnTheScreen()))
        ->and($drawn->said())->toContain($screen->findings()[0]->title);
});
