<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WatchingOneArrive;

use function str_repeat;

use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\Fakes\AStackThatNarrates;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatWalksThrough;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\WorkLeftRunningInMemory;

/**
 * The walkthrough screen, about the one stack the files about that screen look
 * at, and the moments and steps a walk is followed through.
 *
 * Shared by `WatchingOneThingArriveTest` and `HearingWhereAWalkHasGotTest`.
 */
final readonly class TheWalkthroughScreenOfTheLoft
{
    /** The machine a walkthrough is started on. */
    public static function theStackAWalkRunsOn(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('k', Nonce::SHORTEST))),
            StackName::of('The loft'),
            Address::of('https://192.168.1.42:8443'),
            Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
        );
    }

    /**
     * The screen, opened as the router opens it, with a stack it knows and a keychain holding whatever a test says.
     *
     * Mounted, because the router mounts a screen before its first frame and that
     * is where a walk left running is picked up.
     */
    public static function theWalkthroughScreen(
        AStackThatWalksThrough $walking,
        ?AKeychainInMemory $keychain = null,
        bool $signedIn = true,
        ?AStackThatExplainsItsWords $explaining = null,
        ?WorkLeftRunningInMemory $left = null,
        ?AStackThatNarrates $narrating = null,
        ?FrozenClock $clock = null,
        ?ACaptureInMemory $capture = null,
        ?AStackThatSpeaksUp $listing = null,
    ): WatchingOneArrive {
        $stack = self::theStackAWalkRunsOn();
        $keychain ??= AKeychainInMemory::working();

        if ($signedIn) {
            $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
        }

        $screen = new WatchingOneArrive(
            $walking,
            $explaining ?? AStackThatExplainsItsWords::with(TheGlossary::of()),
            $keychain,
            AroundThePhone::holding(StacksInMemory::holding($stack), hearing: $listing),
            $left ?? WorkLeftRunningInMemory::working(),
            $narrating ?? AStackThatNarrates::holdingOpen(),
            $clock ?? FrozenClock::at(self::secondsIntoFollowingAWalk(0)),
            $capture ?? ACaptureInMemory::inFront(),
            settings: new AppsSettingsThatOpen(),
            listening: AroundThePhone::listening(),
        );
        $screen->setParams(['stack' => $stack->id()->stored()]);
        $screen->mount();

        return $screen;
    }

    /** A moment while a walk is followed, counted in seconds from one a test starts at. */
    public static function secondsIntoFollowingAWalk(int $seconds): Instant
    {
        return Instant::atEpochSeconds(1_790_000_000 + $seconds);
    }

    /** A step the walk says on the stream, as the adapter hands it over. */
    public static function aStepTheWalkSaid(WalkthroughStep $step, string $said, string $detail = ''): WhatTheWalkSaid
    {
        return WhatTheWalkSaid::said($detail === ''
            ? ALineItSaid::withoutDetail($step, $said)
            : ALineItSaid::withDetail($step, $said, $detail));
    }
}
