<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;
use Modules\Wayfinding\Api\HearingWhileAway;

use function sprintf;

use Tests\Support\Fakes\ACaptureInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\ANotifierInMemory;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\StandingsInMemory;
use Tests\Support\Fakes\WakesInMemory;

/** A phone the platform has woken while the app is away, holding stacks and hearing them through one stream. */
final readonly class AWakeOnThePhone
{
    public FrozenClock $clock;

    public ANotifierInMemory $notifier;

    public StandingsInMemory $standings;

    public AKeychainInMemory $keychain;

    public TurningNotificationsOn $switch;

    private StacksInMemory $stacks;

    private ACaptureInMemory $capture;

    private function __construct(public AStackThatSpeaksUp $hearing, Instant $at, Stack ...$stacks)
    {
        $this->clock = FrozenClock::at($at);
        $this->notifier = ANotifierInMemory::allowed();
        $this->standings = StandingsInMemory::working();
        $this->keychain = AKeychainInMemory::working();
        $this->stacks = StacksInMemory::holding(...$stacks);
        $this->capture = ACaptureInMemory::away();
        $this->switch = new TurningNotificationsOn(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), ANotifierInMemory::allowed(), $this->clock, WakesInMemory::thatWake());
    }

    /** A phone holding these stacks with notifications on, woken at that moment. */
    public static function holding(AStackThatSpeaksUp $hearing, Instant $at, Stack ...$stacks): self
    {
        $phone = new self($hearing, $at, ...$stacks);
        $phone->switch->turnOn();

        return $phone;
    }

    /** The same, with notifications left off. */
    public static function withNotificationsOff(AStackThatSpeaksUp $hearing, Instant $at, Stack ...$stacks): self
    {
        return new self($hearing, $at, ...$stacks);
    }

    /** The same phone, signed in to that stack as `$whose`. */
    public function signedInTo(Stack $stack, Whose $whose): self
    {
        $this->keychain->keep($stack->id(), Session::of(sprintf('a-session-for-%s', $stack->name()->shown())), $whose);

        return $this;
    }

    /** The same phone, with the app in front of somebody. */
    public function withTheAppOpen(): self
    {
        return clone($this, ['capture' => ACaptureInMemory::inFront()]);
    }

    /** What a wake on this phone hears with. */
    public function hearing(): HearingWhileAway
    {
        return new HearingWhileAway(
            AroundThePhone::listening($this->hearing, $this->clock, $this->capture, standings: $this->standings, notifier: $this->notifier),
            $this->keychain,
            $this->stacks,
            $this->switch,
        );
    }
}
