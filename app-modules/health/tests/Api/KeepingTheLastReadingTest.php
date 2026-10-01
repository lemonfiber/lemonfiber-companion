<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Health\Api\KeepingTheLastReading;
use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatStoppedMoving;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

/** The moment a kept summary in these tests was heard at. */
const WHEN_THE_SUMMARY_WAS_HEARD = 1_790_000_000;

/** Thirty days, in seconds: how long a reading is kept until the operator chooses. */
const THIRTY_DAYS = 2_592_000;

/** The stack a summary is kept for. */
function theStackItIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
}

/** A second stack, whose summary must never answer for the first's. */
function theOtherStackItIsKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST)));
}

/** A moment, counted in seconds from when the summary was heard. */
function secondsAfterItWasHeard(int $seconds): Instant
{
    return Instant::atEpochSeconds(WHEN_THE_SUMMARY_WAS_HEARD + $seconds);
}

/** A summary with every part a summary can have. */
function aSummaryWithEveryPart(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Broken,
        2,
        'The disk is full',
        WhatStoppedMoving::of(
            AStoppage::of(HowItStopped::StalledDownload, 'Four downloads', 4, 'No space left on device', 3_600),
            AStoppage::of(HowItStopped::Slow, 'One film', 1, '', 60),
        ),
        AnAffectedItem::of(
            Check::of('disk.space'),
            Severity::Critical,
            'The disk is full',
            'Nothing new can be downloaded',
            Remedies::of(Remedy::of('Make room'), Remedy::of('Add a disk')),
            WhatFollowedFromIt::of('Imports are failing', 'Downloads have stopped'),
        ),
        AnAffectedItem::of(
            Check::of('vpn.egress'),
            Severity::Advisory,
            'The tunnel is slow',
            'Downloads take longer',
            Remedies::none(),
            WhatFollowedFromIt::of(),
        ),
    );
}

/** A summary saying only a word. */
function aSummaryThatOnlySays(HowItStands $standing): TheHealthSummary
{
    return TheHealthSummary::of($standing, 0, '', WhatStoppedMoving::nothing());
}

/** Everything a summary says, as one line, so two summaries can be compared whole. */
function everythingItSays(TheHealthSummary $summary): string
{
    $said = [$summary->standing()->value, (string) $summary->wantingAttention(), $summary->worst()];

    foreach ($summary as $item) {
        $remedies = [];

        foreach ($item->remedies() as $remedy) {
            $remedies[] = $remedy->action();
        }

        $downstream = [];

        foreach ($item->downstream() as $also) {
            $downstream[] = $also;
        }

        $said[] = sprintf(
            'item(%s,%s,%s,%s,[%s],[%s])',
            $item->check()->shown(),
            $item->severity()->value,
            $item->summary(),
            $item->meaning(),
            implode(';', $remedies),
            implode(';', $downstream),
        );
    }

    foreach ($summary->stopped() as $row) {
        $said[] = sprintf('stopped(%s,%s,%d,%s,%d)', $row->how()->value, $row->name(), $row->items(), $row->blocking(), $row->heldFor()->inSeconds());
    }

    return implode(' | ', $said);
}

/** What a screen opening on a stack holds, as one line: nothing, or the summary as of when. */
function whatTheScreenOpensOn(WhatWasHeardSoFar $held): string
{
    return $held->summary(
        none: static fn(): Code => Code::of('nothing'),
        current: static fn(TheHealthSummary $summary): Code => Code::of(sprintf('current %s', everythingItSays($summary))),
        asOf: static fn(TheHealthSummary $summary, Instant $at): Code
            => Code::of(sprintf('as of %d: %s', $at->epochSeconds() - WHEN_THE_SUMMARY_WAS_HEARD, everythingItSays($summary))),
    )->shown();
}

/** Whether a keep was noted as written, as one word. */
function whetherItWasKept(Noted $noted): string
{
    return $noted->either(
        down: static fn(): Code => Code::of('kept'),
        notKept: static fn(): Code => Code::of('not kept'),
    )->shown();
}

/** A seal, a store, and what decides between them, over the two stand-ins. */
final readonly class WhatIsKeptOfHealth
{
    public KeepingTheLastReading $keeping;

    public ReadingsKeptForInMemory $settings;

    public function __construct(public ASealInMemory $seal, public HealthReadingsInMemory $store, ?Instant $now = null)
    {
        $this->settings = ReadingsKeptForInMemory::standard();
        $this->keeping = new KeepingTheLastReading($seal, $store, $this->settings, FrozenClock::at($now ?? Instant::atEpochSeconds(0)));
    }

    /** The same, with the clock reading this. */
    public static function onAPhoneThatSealsAt(Instant $now): self
    {
        return new self(ASealInMemory::working(), HealthReadingsInMemory::empty(), $now);
    }

    public static function onAPhoneThatSeals(): self
    {
        return new self(ASealInMemory::working(), HealthReadingsInMemory::empty());
    }

    /** A value kept for a stack as though a summary had been, sealed by this phone. */
    public function holdsSealed(string $written): self
    {
        $this->seal->seal(Unsealed::of($written))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->store->keep(
                $this->seal->stack(theStackItIsKeptFor()),
                $payload,
                Shape::One,
                secondsAfterItWasHeard(0),
            ),
            refused: static fn(): Noted => Noted::notKept(),
        );

        return $this;
    }
}

it('keeps a summary sealed, and hands it back on opening as of when it was heard', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();

    expect(whetherItWasKept($kept->keeping->keep(theStackItIsKeptFor(), aSummaryWithEveryPart(), secondsAfterItWasHeard(0))))->toBe('kept')
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))
        ->toBe(sprintf('as of 0: %s', everythingItSays(aSummaryWithEveryPart())));
});

it('keeps nothing the store could read', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryWithEveryPart(), secondsAfterItWasHeard(0));

    $payload = $kept->store->newest($kept->seal->stack(theStackItIsKeptFor()))->either(
        found: static fn(SealedPayload $payload): Code => Code::of($payload->forTheStore()),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();

    expect($payload)->toStartWith('sealed-by-')
        ->and($payload)->not->toContain('disk')
        ->and($kept->store->newest(SealedStack::of(theStackItIsKeptFor()->stored()))->either(
            found: static fn(): Code => Code::of('found by identity'),
            none: static fn(): Code => Code::of('none'),
            unreadable: static fn(): Code => Code::of('unreadable'),
        )->shown())->toBe('none');
});

it('keeps the newest summary of each stack, and each stack\'s apart', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Healthy), secondsAfterItWasHeard(30));
    $kept->keeping->keep(theOtherStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Stopped), secondsAfterItWasHeard(60));

    expect(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('as of 30: healthy | 0 |')
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theOtherStackItIsKeptFor())))->toBe('as of 60: stopped | 0 |');
});

it('opens on nothing where nothing was kept', function (): void {
    expect(whatTheScreenOpensOn(WhatIsKeptOfHealth::onAPhoneThatSeals()->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing');
});

it('keeps nothing where nothing can be sealed', function (): void {
    $kept = new WhatIsKeptOfHealth(ASealInMemory::withNoSecureStorage(), HealthReadingsInMemory::empty());

    expect(whetherItWasKept($kept->keeping->keep(theStackItIsKeptFor(), aSummaryWithEveryPart(), secondsAfterItWasHeard(0))))->toBe('not kept')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('keeps nothing where a stack\'s words are not text it can write', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $garbled = TheHealthSummary::of(HowItStands::Broken, 0, "\xB1\x31", WhatStoppedMoving::nothing());

    expect(whetherItWasKept($kept->keeping->keep(theStackItIsKeptFor(), $garbled, secondsAfterItWasHeard(0))))->toBe('not kept')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('says what the store answered where it would not keep a sealed summary', function (): void {
    $kept = new WhatIsKeptOfHealth(ASealInMemory::working(), HealthReadingsInMemory::unreachable());

    expect(whetherItWasKept($kept->keeping->keep(theStackItIsKeptFor(), aSummaryWithEveryPart(), secondsAfterItWasHeard(0))))->toBe('not kept');
});

it('lets go of a kept reading a later build wrote, and opens on nothing', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackItIsKeptFor()));

    expect(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that does not open, and opens on nothing', function (): void {
    // Sealed by another phone's seal, which is what a payload sealed under a
    // key this one does not hold looks like from here. This phone's keys are
    // made first, so what fails to open is the payload and not a key made at
    // the moment of opening.
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $elsewhere = ASealInMemory::working();
    $kept->seal->standing();

    $elsewhere->seal(Unsealed::of('{}'))->either(
        sealed: static fn(SealedPayload $payload): Noted => $kept->store->keep(
            $kept->seal->stack(theStackItIsKeptFor()),
            $payload,
            Shape::One,
            secondsAfterItWasHeard(0),
        ),
        refused: static fn(): Noted => Noted::notKept(),
    );

    expect(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that opens to something that is not a summary', function (string $written): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals()->holdsSealed($written);

    expect(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
})->with([
    'not written as fields at all' => ['not a summary'],
    'a word rather than fields' => ['"healthy"'],
    'a field left out' => ['{"standing":"healthy","wanting":0,"worst":"","affected":[]}'],
    'a word that is not text' => ['{"standing":1,"wanting":0,"worst":"","affected":[],"stopped":[]}'],
    'a word this build does not know' => ['{"standing":"splendid","wanting":0,"worst":"","affected":[],"stopped":[]}'],
    'a count that is not a number' => ['{"standing":"healthy","wanting":"none","worst":"","affected":[],"stopped":[]}'],
    'a count below nothing' => ['{"standing":"healthy","wanting":-1,"worst":"","affected":[],"stopped":[]}'],
    'items that are not a list' => ['{"standing":"healthy","wanting":0,"worst":"","affected":"none","stopped":[]}'],
    'an item that is not fields' => ['{"standing":"healthy","wanting":0,"worst":"","affected":["disk"],"stopped":[]}'],
    'an item with no check' => ['{"standing":"broken","wanting":1,"worst":"","affected":[{"check":" ","severity":"error","summary":"","meaning":"","remedies":[],"downstream":[]}],"stopped":[]}'],
    'an item of a severity this build does not know' => ['{"standing":"broken","wanting":1,"worst":"","affected":[{"check":"disk","severity":"dire","summary":"","meaning":"","remedies":[],"downstream":[]}],"stopped":[]}'],
    'a remedy that is not text' => ['{"standing":"broken","wanting":1,"worst":"","affected":[{"check":"disk","severity":"error","summary":"","meaning":"","remedies":[1],"downstream":[]}],"stopped":[]}'],
    'a row stopped in a way this build does not know' => ['{"standing":"broken","wanting":0,"worst":"","affected":[],"stopped":[{"how":"stuck","name":"a film","items":1,"blocking":"","held":60}]}'],
    'a row standing for a count that is not a number' => ['{"standing":"broken","wanting":0,"worst":"","affected":[],"stopped":[{"how":"slow","name":"a film","items":"one","blocking":"","held":60}]}'],
]);

it('forgets every reading kept longer than thirty days, and keeps one read exactly thirty days ago', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));
    $kept->keeping->keep(theOtherStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Healthy), secondsAfterItWasHeard(1));

    expect($kept->keeping->forgetTheOld(secondsAfterItWasHeard(THIRTY_DAYS + 1))->howMany())->toBe(1)
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theOtherStackItIsKeptFor())))->toBe('as of 1: healthy | 0 |');
});

it('forgets nothing at a moment less than thirty days after the clock began', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), Instant::atEpochSeconds(0));

    expect($kept->keeping->forgetTheOld(Instant::atEpochSeconds(THIRTY_DAYS - 1))->howMany())->toBe(0);
});

it('finds nothing and lets go of nothing where the seal\'s keys cannot be read', function (): void {
    // The stack's hash is taken under a key kept nowhere, so it matches no
    // row: what was sealed stays for the day the keys read again.
    $store = HealthReadingsInMemory::empty();
    $sealing = ASealInMemory::working();
    new KeepingTheLastReading($sealing, $store, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)))->keep(theStackItIsKeptFor(), aSummaryWithEveryPart(), secondsAfterItWasHeard(0));

    $locked = new KeepingTheLastReading(ASealInMemory::thatWillNotOpen(), $store, ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));

    expect(whatTheScreenOpensOn($locked->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('keeps readings for thirty days until the operator chooses', function (): void {
    expect(WhatIsKeptOfHealth::onAPhoneThatSeals()->keeping->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue();
});

it('keeps the operator\'s choice, and lets go of every reading older than it at once', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSealsAt(secondsAfterItWasHeard(8 * SecondsIn::ADay->value));
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));
    $kept->keeping->keep(theOtherStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Healthy), secondsAfterItWasHeard(2 * SecondsIn::ADay->value));

    $inForce = $kept->keeping->keepFor(HowLongReadingsAreKept::for(KeptFor::SevenDays));

    expect($inForce->is(KeptFor::SevenDays))->toBeTrue()
        ->and($kept->keeping->keptFor()->is(KeptFor::SevenDays))->toBeTrue()
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing')
        ->and(whatTheScreenOpensOn($kept->keeping->lastKept(theOtherStackItIsKeptFor())))->not->toBe('nothing');
});

it('forgets the old by the length the operator chose', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keepFor(HowLongReadingsAreKept::days(3));
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));

    expect($kept->keeping->forgetTheOld(secondsAfterItWasHeard(3 * SecondsIn::ADay->value))->howMany())->toBe(0)
        ->and($kept->keeping->forgetTheOld(secondsAfterItWasHeard(3 * SecondsIn::ADay->value + 1))->howMany())->toBe(1);
});

it('lets go of no reading for its age where they are kept until removed', function (): void {
    $kept = WhatIsKeptOfHealth::onAPhoneThatSeals();
    $kept->keeping->keepFor(HowLongReadingsAreKept::untilRemoved());
    $kept->keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));

    expect($kept->keeping->forgetTheOld(secondsAfterItWasHeard(10 * THIRTY_DAYS))->howMany())->toBe(0)
        ->and($kept->keeping->keptFor()->isUntilRemoved())->toBeTrue();
});

it('lets go by the choice in force where the phone could not keep it', function (): void {
    $store = HealthReadingsInMemory::empty();
    $keeping = new KeepingTheLastReading(ASealInMemory::working(), $store, ReadingsKeptForInMemory::keepingNothing(), FrozenClock::at(secondsAfterItWasHeard(8 * SecondsIn::ADay->value)));
    $keeping->keep(theStackItIsKeptFor(), aSummaryThatOnlySays(HowItStands::Broken), secondsAfterItWasHeard(0));

    expect($keeping->keepFor(HowLongReadingsAreKept::for(KeptFor::SevenDays))->is(KeptFor::SevenDays))->toBeTrue()
        ->and($keeping->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue()
        ->and(whatTheScreenOpensOn($keeping->lastKept(theStackItIsKeptFor())))->toBe('nothing');
});
