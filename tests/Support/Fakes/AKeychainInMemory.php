<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\Kept;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhySessionCannotBeKept;

/**
 * A secure store that keeps nothing beyond the test, and can be told to refuse.
 *
 * `G1` forbids mocking a type we do not own and this is the other half: a fake
 * of our own port, written by hand, held to the same contract test as the real
 * one. What it must not become is a fake that is easier to satisfy than a
 * keychain — so both refusals are reachable from here, because a device with no
 * store is the case the real adapter exists to report and the fake would
 * otherwise never exercise.
 */
final class AKeychainInMemory implements SecureStorage
{
    /** @var array<string, Session> */
    private array $kept = [];

    private function __construct(private readonly ?WhySessionCannotBeKept $refusing) {}

    /** A working store on a device that has one. */
    public static function working(): self
    {
        return new self(null);
    }

    /** A device with nowhere safe to put a session (`N4-R6`). */
    public static function withNowhereSafe(): self
    {
        return new self(WhySessionCannotBeKept::DeviceHasNoSecureStorage);
    }

    /** A store that is there and will not open. */
    public static function thatWillNotOpen(): self
    {
        return new self(WhySessionCannotBeKept::StoreWouldNotOpen);
    }

    public function isAvailable(): bool
    {
        return ! $this->refusing instanceof WhySessionCannotBeKept;
    }

    public function keep(StackId $stack, Session $session): Kept
    {
        if ($this->refusing instanceof WhySessionCannotBeKept) {
            return Kept::refused($this->refusing);
        }

        $this->kept[$stack->stored()] = $session;

        return Kept::safely();
    }

    public function forget(StackId $stack): Kept
    {
        unset($this->kept[$stack->stored()]);

        return Kept::safely();
    }

    public function resume(StackId $stack): Resumed
    {
        // A refusing store answers `notHeld()` rather than what it is holding,
        // which matches the adapter: a keychain that will not open is a
        // keychain with no session in it as far as resuming goes. A fake that
        // handed the session back anyway would make a launch against a locked
        // store pass here and fail on a phone.
        if ($this->refusing instanceof WhySessionCannotBeKept) {
            return Resumed::notHeld();
        }

        $held = $this->kept[$stack->stored()] ?? null;

        return $held instanceof Session ? Resumed::with($held) : Resumed::notHeld();
    }

    /** Whether this store is holding a session for that stack — for a test to ask. */
    public function isHolding(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->kept);
    }
}
