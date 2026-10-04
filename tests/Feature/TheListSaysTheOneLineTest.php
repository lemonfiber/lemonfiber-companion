<?php

declare(strict_types=1);

use Modules\Connection\Api\Opening;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\HowOftenAScreenLooks;
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
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\ADeviceOnANetwork;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\NoticingWhatIsNew;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhatThePhoneKeeps;

// The app opens on how each stack stands, and says when that was heard.
//
// The word is the core's one line, which is heard on the event stream and
// kept. The list's rows read back what was kept, so every word on them is a
// retained reading, said as a single word and carrying when it was heard. Once
// its first frame is up, the list listens to each stack it is signed into as
// the operator, and what it hears is kept too.

/** The moment every case below is read at, so an age is a thing a test states. */
const NOW = 1_770_000_000;

/** Named for this file: the root suites share one namespace (`G10`). */
function aStackToOpenOn(string $called = 'The loft', string $seed = 'a'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** The launch screen, over stacks and words a test states. */
function theOpeningScreen(Stack $stack, ?StandingsInMemory $standings = null): YourStacks
{
    $stacks = StacksInMemory::holding($stack);

    return new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $standings ?? StandingsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds(NOW)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );
}

/** One line of the catalogue, as text, for finding it on a row. */
function aWordOnTheList(string $key): string
{
    $said = __($key);

    return is_string($said) ? $said : $key;
}

/** What a stack's row says, as the word's key and the age's key and count. */
function whatTheRowSays(Stack $stack, StandingsInMemory $standings): string
{
    $said = theOpeningScreen($stack, $standings)->lastKnownOf($stack);

    return sprintf('%s|%s|%d', $said->said, $said->ago->said, $said->ago->count);
}

it('opens on the word the stack\'s screen last heard, and says when it was heard', function (): void {
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working()
        ->lastHeard($stack->id(), HowItStands::Degraded, Instant::atEpochSeconds(NOW - 10));

    // The row says the word and its age on the one line under its name, and
    // nothing about the session, which this device holds none of here.
    $drawn = WhatTheDeviceWouldDraw::by(theOpeningScreen($stack, $standings))->said();

    expect(whatTheRowSays($stack, $standings))->toBe('health.standing.degraded|health.ago.minutes|0')
        ->and($drawn)->toContain(sprintf(
            '%s · %s · %s',
            aWordOnTheList(HowItStands::Degraded->saidInAWord()),
            trans_choice('health.ago.minutes', 0),
            aWordOnTheList('connection.sign_in_needed'),
        ))
        ->and(implode("\n", $drawn))->not->toContain(__(HowItStands::Degraded->saidOnTheScreen()));
});

it('says nothing about the session where this device is signed in', function (): void {
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working()
        ->lastHeard($stack->id(), HowItStands::Critical, Instant::atEpochSeconds(NOW - 7_200));
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $stacks = StacksInMemory::holding($stack);

    $screen = new YourStacks(
        $stacks,
        $keychain,
        AShareSheetThatWasOffered::working(),
        $standings,
        FrozenClock::at(Instant::atEpochSeconds(NOW)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(sprintf(
        '%s · %s',
        aWordOnTheList(HowItStands::Unknown->saidInAWord()),
        trans_choice('health.ago.hours', 2),
    ));
});

it('says a stack whose one line was never heard cannot be told, and never that it is fine', function (): void {
    $stack = aStackToOpenOn();

    $drawn = WhatTheDeviceWouldDraw::by(theOpeningScreen($stack))->said();

    // The word alone, with no age, because nothing was heard to have one.
    expect(whatTheRowSays($stack, StandingsInMemory::working()))->toBe('health.standing.unknown||0')
        ->and($drawn)->toContain(sprintf('%s · %s', aWordOnTheList(HowItStands::Unknown->saidInAWord()), aWordOnTheList('connection.sign_in_needed')))
        ->and(implode("\n", $drawn))->not->toContain(__(HowItStands::Healthy->saidInAWord()));
});

it('reads a word heard longer ago than a stream may be silent as unknown, with when it was heard', function (): void {
    // Thirty seconds is twice the heartbeat, the longest the stack's own screen
    // takes a summary as current with nothing heard. The list vouches for the
    // word exactly as long, and past that says what that screen would say.
    $stack = aStackToOpenOn();
    $heardAt = static fn(int $ago): StandingsInMemory => StandingsInMemory::working()
        ->lastHeard($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(NOW - $ago));

    $drawn = WhatTheDeviceWouldDraw::by(theOpeningScreen($stack, $heardAt(7_200)))->said();

    expect(whatTheRowSays($stack, $heardAt(30)))->toBe('health.standing.healthy|health.ago.minutes|0')
        ->and(whatTheRowSays($stack, $heardAt(31)))->toBe('health.standing.unknown|health.ago.minutes|0')
        ->and(whatTheRowSays($stack, $heardAt(7_200)))->toBe('health.standing.unknown|health.ago.hours|2')
        ->and($drawn)->toContain(sprintf(
            '%s · %s · %s',
            aWordOnTheList(HowItStands::Unknown->saidInAWord()),
            trans_choice('health.ago.hours', 2),
            aWordOnTheList('connection.sign_in_needed'),
        ))
        ->and(implode("\n", $drawn))->not->toContain(__(HowItStands::Healthy->saidInAWord()));
});

it('says the age in whichever unit it fills', function (): void {
    // Three bands, and the boundaries are the point: one second under an hour
    // is still minutes, and one second over is an hour. A band chosen by `>=`
    // that should be `>` moves every reading on the screen by one unit, which
    // nothing but a case at the boundary can catch.
    $stack = aStackToOpenOn();

    $said = static function (int $ago) use ($stack): string {
        $shown = theOpeningScreen($stack, StandingsInMemory::working()
            ->lastHeard($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(NOW - $ago)))
            ->lastKnownOf($stack);

        return sprintf('%s|%d', $shown->ago->said, $shown->ago->count);
    };

    expect($said(0))->toBe('health.ago.minutes|0')
        ->and($said(59))->toBe('health.ago.minutes|0')
        ->and($said(60))->toBe('health.ago.minutes|1')
        ->and($said(3_599))->toBe('health.ago.minutes|59')
        ->and($said(3_600))->toBe('health.ago.hours|1')
        ->and($said(86_399))->toBe('health.ago.hours|23')
        ->and($said(86_400))->toBe('health.ago.days|1')
        ->and($said(864_000))->toBe('health.ago.days|10');
});

it('says a word heard in the future was heard just now', function (): void {
    // A device whose clock moved backwards, or a stack whose clock is ahead.
    // This app knows the word is not old and does not know enough to say
    // anything else; the raw subtraction would put *in three hours* on the
    // screen, and nought seconds is what the count says.
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working()
        ->lastHeard($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(NOW + 10_800));

    expect(whatTheRowSays($stack, $standings))->toBe('health.standing.healthy|health.ago.minutes|0');
});

it('L1 — every band names a line, and it counts on the number beside it', function (): void {
    // `trans_choice` is what the template calls, because *a minute ago* and
    // *two minutes ago* are not the same sentence in either language this app
    // speaks. A line written without the plural forms renders the same words
    // for one and for many, which reads as a bug in the clock.
    foreach (HowLongAgo::cases() as $unit) {
        expect(trans_choice($unit->saidOnTheScreen(), 1))
            ->not->toBe($unit->saidOnTheScreen(), $unit->name)
            ->and(trans_choice($unit->saidOnTheScreen(), 2))
            ->not->toBe(trans_choice($unit->saidOnTheScreen(), 1), $unit->name);
    }
});

it('keeps one stack\'s word apart from another\'s', function (): void {
    $loft = aStackToOpenOn('The loft', 'a');
    $shed = aStackToOpenOn('The shed', 'b');

    $standings = StandingsInMemory::working()
        ->lastHeard($loft->id(), HowItStands::Broken, Instant::atEpochSeconds(NOW - 5))
        ->lastHeard($shed->id(), HowItStands::Stopped, Instant::atEpochSeconds(NOW - 5));

    $stacks = StacksInMemory::holding($loft, $shed);
    $screen = new YourStacks(
        $stacks,
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $standings,
        FrozenClock::at(Instant::atEpochSeconds(NOW)),
        new Opening($stacks, ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        AStackThatSpeaksUp::holdingOpen(),
        ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );

    expect($screen->lastKnownOf($loft)->said)->toBe(HowItStands::Broken->saidOnTheScreen())
        ->and($screen->lastKnownOf($shed)->said)->toBe(HowItStands::Stopped->saidOnTheScreen());
});

it('reads a held value that is not a word, or one with no moment, as never heard', function (): void {
    // The port answers `Reading`, whose retained arm is typed `object` — it
    // carries whatever was put in it, which for a store is whatever the last
    // build wrote. A live reading has no moment to say an age with.
    $stack = aStackToOpenOn();
    $somethingElse = StandingsInMemory::working()->lastHeardAsSomethingElse($stack->id(), Instant::atEpochSeconds(NOW - 5));
    $live = StandingsInMemory::working()->heardLive($stack->id(), HowItStands::Healthy);

    expect(whatTheRowSays($stack, $somethingElse))->toBe('health.standing.unknown||0')
        ->and(whatTheRowSays($stack, $live))->toBe('health.standing.unknown||0');
});

it('says on the list the word the stack\'s own screen just heard', function (): void {
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $heard = new HowThisStackIs(
        AStackThatWasAsked::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        AStackThatSpeaksUp::holdingOpen(WhatWasHeard::said(TheHealthSummary::of(HowItStands::Advisory, 1, 'A note', WhatStoppedMoving::nothing()))),
        FrozenClock::at(Instant::atEpochSeconds(NOW - 3)),
        ACaptureInMemory::inFront(),
        $standings,
        WhatThePhoneKeeps::nothingYet(),
        new AppsSettingsThatOpen(),
        NoticingWhatIsNew::fromNothing(),
    );
    $heard->setParams(['stack' => $stack->id()->stored()]);
    $heard->listen();

    expect(whatTheRowSays($stack, $standings))->toBe('health.standing.advisory|health.ago.minutes|0')
        ->and(theOpeningScreen($stack, $standings)->lastKnownOf($stack)->said)->toBe($heard->summary()->said);
});

/** The launch screen, listening with what a test hands it. */
function theListeningScreen(
    Stack $stack,
    StandingsInMemory $standings,
    AKeychainInMemory $keychain,
    AStackThatSpeaksUp $hearing,
    ?ACaptureInMemory $capture = null,
    ?FrozenClock $clock = null,
    ?ADeviceOnANetwork $network = null,
): YourStacks {
    $stacks = StacksInMemory::holding($stack);

    return new YourStacks(
        $stacks,
        $keychain,
        AShareSheetThatWasOffered::working(),
        $standings,
        $clock ?? FrozenClock::at(Instant::atEpochSeconds(NOW)),
        new Opening($stacks, $network ?? ADeviceOnANetwork::connected()),
        WhatThePhoneKeeps::nothingToClear(),
        WhatThePhoneKeeps::nothingYet(),
        $hearing,
        $capture ?? ACaptureInMemory::inFront(),
        WhatThePhoneKeeps::nothingToFinish(),
        AroundThePhone::alreadyOpened(),
        new AppsSettingsThatOpen(),
    );
}

/** A keychain holding a session for that stack, as whoever it is given. */
function aKeychainSignedInto(Stack $stack, Whose $whose): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), $whose);

    return $keychain;
}

/** A stack saying it needs attention now. */
function aStackSayingItIsCritical(): WhatWasHeard
{
    return WhatWasHeard::said(TheHealthSummary::of(HowItStands::Critical, 1, 'The tunnel is down', WhatStoppedMoving::nothing()));
}

it('draws the kept word with its age before it asks, and the word it hears after', function (): void {
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working()
        ->lastHeard($stack->id(), HowItStands::Healthy, Instant::atEpochSeconds(NOW - 10_800));
    $hearing = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());
    $screen = theListeningScreen($stack, $standings, aKeychainSignedInto($stack, Whose::theOperator()), $hearing);

    // The first frame is what the device held: three hours old, so unknown,
    // and saying when it was heard. Nothing was asked to draw it.
    $first = implode("\n", WhatTheDeviceWouldDraw::by($screen)->said());

    $stale = sprintf('%s · %s', aWordOnTheList(HowItStands::Unknown->saidInAWord()), trans_choice('health.ago.hours', 3));

    expect($first)->toContain($stale)
        ->and($hearing->asked())->toBe(0);

    $screen->listen();

    $after = implode("\n", WhatTheDeviceWouldDraw::by($screen)->said());

    expect($hearing->asked())->toBe(1)
        ->and($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Critical->saidOnTheScreen())
        ->and($after)->toContain(aWordOnTheList(HowItStands::Critical->saidInAWord()))
        ->and($after)->not->toContain($stale);
});

it('keeps listening on the wakes after the first, and says the newest word', function (): void {
    $stack = aStackToOpenOn();
    $standings = StandingsInMemory::working();
    $hearing = AStackThatSpeaksUp::holdingOpen(
        WhatWasHeard::nothing(),
        aStackSayingItIsCritical(),
        WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())),
    );
    $screen = theListeningScreen($stack, $standings, aKeychainSignedInto($stack, Whose::theOperator()), $hearing);

    $screen->listen();
    expect($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Unknown->saidOnTheScreen());

    $screen->listen();
    expect($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Critical->saidOnTheScreen());

    $screen->listen();
    expect($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Healthy->saidOnTheScreen())
        ->and($hearing->asked())->toBe(3);
});

it('asks no stack it holds no operator session for', function (): void {
    $stack = aStackToOpenOn();

    foreach ([
        'no session' => AKeychainInMemory::working(),
        'a member\'s session' => aKeychainSignedInto($stack, Whose::member('member-1')),
    ] as $which => $keychain) {
        $hearing = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());
        $screen = theListeningScreen($stack, StandingsInMemory::working(), $keychain, $hearing);

        $screen->listen();

        expect($hearing->asked())->toBe(0, $which)
            ->and($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Unknown->saidOnTheScreen(), $which);
    }
});

it('waits out a break before it asks a stack that could not be heard again', function (): void {
    $stack = aStackToOpenOn();
    $clock = FrozenClock::at(Instant::atEpochSeconds(NOW));
    $hearing = AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), aStackSayingItIsCritical());
    $screen = theListeningScreen($stack, StandingsInMemory::working(), aKeychainSignedInto($stack, Whose::theOperator()), $hearing, clock: $clock);

    $screen->listen();
    $screen->listen();

    expect($hearing->asked())->toBe(1);

    $clock->moveTo(Instant::atEpochSeconds(NOW + HowOftenAScreenLooks::AfterABreak->seconds()));
    $screen->listen();

    expect($hearing->asked())->toBe(2)
        ->and($screen->lastKnownOf($stack)->said)->toBe(HowItStands::Critical->saidOnTheScreen());
});

it('lets go of a session the stack refused, so the row asks to sign in', function (): void {
    $stack = aStackToOpenOn();
    $keychain = aKeychainSignedInto($stack, Whose::theOperator());
    $screen = theListeningScreen(
        $stack,
        StandingsInMemory::working(),
        $keychain,
        AStackThatSpeaksUp::holdingOpen(WhatWasHeard::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))),
    );

    $screen->listen();

    expect($keychain->isHolding($stack->id()))->toBeFalse()
        ->and($screen->isSignedInto($stack))->toBeFalse();
});

it('lets go of every stream while nobody can see the list, and when it is left', function (): void {
    $stack = aStackToOpenOn();
    $keychain = aKeychainSignedInto($stack, Whose::theOperator());
    $away = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());

    theListeningScreen($stack, StandingsInMemory::working(), $keychain, $away, ACaptureInMemory::away())->listen();

    expect($away->asked())->toBe(0)
        ->and($away->lettingsGo())->toBe(1);

    $left = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());
    $screen = theListeningScreen($stack, StandingsInMemory::working(), $keychain, $left);
    $screen->listen();
    $screen->stop();

    expect($left->lettingsGo())->toBe(1);
});

it('asks no stack on a launch that found no network', function (): void {
    $stack = aStackToOpenOn();
    $hearing = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());
    $screen = theListeningScreen(
        $stack,
        StandingsInMemory::working(),
        aKeychainSignedInto($stack, Whose::theOperator()),
        $hearing,
        network: ADeviceOnANetwork::withNothingToReachOver(),
    );

    $screen->listen();

    expect($hearing->asked())->toBe(0);
});

it('lets go of a stream gone quiet past the contract\'s bound', function (): void {
    $stack = aStackToOpenOn();
    $clock = FrozenClock::at(Instant::atEpochSeconds(NOW));
    $hearing = AStackThatSpeaksUp::holdingOpen(aStackSayingItIsCritical());
    $screen = theListeningScreen($stack, StandingsInMemory::working(), aKeychainSignedInto($stack, Whose::theOperator()), $hearing, clock: $clock);

    $screen->listen();
    $clock->moveTo(Instant::atEpochSeconds(NOW + 31));
    $screen->listen();

    expect($hearing->lettingsGo())->toBe(1);
});
