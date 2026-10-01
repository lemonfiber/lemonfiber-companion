<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Operator\Internal\HearingEachStack;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\WhatItListensWith;
use Modules\Operator\Internal\WhereAStackOpens;
use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
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
            new HearingEachStack(
                new WhatItListensWith(
                    $hearing ?? AStackThatSpeaksUp::holdingOpen(),
                    $clock,
                    $capture ?? ACaptureInMemory::inFront(),
                    $standings,
                    WhatThePhoneKeeps::nothingYet(),
                ),
                $storage,
            ),
            $was ?? WhereTheOperatorWasInMemory::nowhere(),
        );
    }
}
