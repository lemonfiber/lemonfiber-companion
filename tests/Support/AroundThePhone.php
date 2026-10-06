<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;

use Illuminate\Contracts\Translation\Translator;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\News\Api\Noticing;
use Modules\Operator\Internal\WhereAStackOpens;
use Modules\Wayfinding\Api\HearingEachStack;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AStackThatOffers;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WhereTheOperatorWasInMemory;

/**
 * The way around a test's stacks, made as the container makes it.
 *
 * Every screen about a stack is handed one, so a test that builds a screen by
 * hand takes it from here, and what it is made of is written once. A test
 * about the list of stacks hands it what the phone kept of each stack, which
 * sessions it holds, and what the stacks say while the list listens; every
 * other test takes a phone that kept nothing and stacks that say nothing.
 */
final readonly class AroundThePhone
{
    /** The moment a phone that was handed no clock reads. */
    private const int NOW = 1_790_000_000;

    /**
     * The opening, already landed: a list built by a test is somebody coming
     * back to it, which stays on the list.
     */
    public static function alreadyOpened(): WhereAStackOpens
    {
        $opening = new WhereAStackOpens(self::holding(StacksInMemory::working()));
        $opening->theOpening();

        return $opening;
    }

    public static function holding(
        Stacks $stacks,
        ?Standings $standings = null,
        ?SecureStorage $storage = null,
        ?Clock $clock = null,
        ?Hearing $hearing = null,
        ?Capture $capture = null,
        ?WhereTheOperatorWas $was = null,
        ?KnowingWhatAStackOffers $offering = null,
    ): TheWayAround {
        $standings ??= StandingsInMemory::working();
        $clock ??= FrozenClock::at(Instant::atEpochSeconds(self::NOW));
        $storage ??= AKeychainInMemory::working();

        return new TheWayAround(
            $stacks,
            app(Translator::class),
            $standings,
            $clock,
            $storage,
            new HearingEachStack(self::listening($hearing, $clock, $capture, $standings), $storage),
            $was ?? WhereTheOperatorWasInMemory::nowhere(),
            $offering ?? AStackThatOffers::everything(),
        );
    }

    /**
     * The ports a screen holding its stack's stream is handed, made as the container makes them.
     *
     * A stream that says nothing, a phone in front that kept nothing, what
     * notices news on a phone that kept none, and the moment a phone handed no
     * clock reads, for whatever a test does not hand it.
     */
    public static function listening(
        ?Hearing $hearing = null,
        ?Clock $clock = null,
        ?Capture $capture = null,
        ?Standings $standings = null,
        ?KeepingTheLastReading $keeping = null,
        ?Noticing $noticing = null,
    ): WhatItListensWith {
        return new WhatItListensWith(
            $hearing ?? AStackThatSpeaksUp::holdingOpen(),
            $clock ?? FrozenClock::at(Instant::atEpochSeconds(self::NOW)),
            $capture ?? ACaptureInMemory::inFront(),
            $standings ?? StandingsInMemory::working(),
            $keeping ?? WhatThePhoneKeeps::nothingYet(),
            $noticing ?? NoticingWhatIsNew::fromNothing(),
        );
    }
}
