<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function array_keys;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WasRead;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Lemonfiber\Native\WhyNothingWasKept;
use Lemonfiber\Native\Wrote;
use Override;

/**
 * A stand-in for the device's own store, written by hand.
 *
 * `G1` forbids *mocking*, and this is not one: it is an implementation of
 * {@see Keeps} with the four methods written out, which is the same thing
 * `FrozenClock` is to a clock. The distinction matters — a mock asserts on
 * calls and drifts silently when the real shape changes, and this fails to
 * compile.
 *
 * It exists because the three adapters over the store cannot otherwise be run
 * at all. There is no keychain behind a PHP process on a laptop, so without
 * this they are files nothing executes, and what they actually decide — which
 * key a stack gets, which refusal a failure is, that forgetting ignores the
 * result, that an unreadable store is not a device holding nothing — goes
 * unchecked until somebody holds a phone.
 *
 * **Three devices rather than a switch per method.** A store is one of these
 * for the whole of a test, which is what a device is: the combination *reads
 * fine and refuses every write* describes no handset, and a fake able to
 * express it is a fake that makes an adapter pass against a device that does
 * not exist.
 */
final class APlatformStore implements Keeps
{
    /** @var array<string, string> */
    private array $held = [];

    private function __construct(private readonly ?WhyNothingWasKept $refusing) {}

    /** A store that works, on a device that has one. */
    public static function working(): self
    {
        return new self(refusing: null);
    }

    /** A device with no secure store at all. */
    public static function absent(): self
    {
        return new self(refusing: WhyNothingWasKept::NoStoreOnThisDevice);
    }

    /** A store that is there and will not open. */
    public static function refusing(): self
    {
        return new self(refusing: WhyNothingWasKept::StoreWouldNotOpen);
    }

    /**
     * A store that will not open still answers that there is somewhere to keep
     * a session, because there is — what is wrong is a condition trying again
     * can clear. Only a device with no store at all answers no, which is what
     * {@see \Lemonfiber\Native\Storage::canBeAsked()} does.
     */
    #[Override]
    public function canBeAsked(): bool
    {
        return $this->refusing !== WhyNothingWasKept::NoStoreOnThisDevice;
    }

    #[Override]
    public function keep(string $key, string $value, WhenAValueMayBeRead $when): Wrote
    {
        if ($this->refusing instanceof WhyNothingWasKept) {
            return Wrote::refused($this->refusing);
        }

        $this->held[$key] = $value;

        return Wrote::done($when);
    }

    #[Override]
    public function read(string $key): WasRead
    {
        if ($this->refusing instanceof WhyNothingWasKept) {
            return WasRead::refused($this->refusing);
        }

        return array_key_exists($key, $this->held)
            ? WasRead::found($this->held[$key])
            : WasRead::nothing();
    }

    /**
     * Forgetting works on a store that refuses everything else.
     *
     * The one thing that must always work: a refusal leaves the app holding a
     * session it could not store, and getting rid of it cannot depend on the
     * store that just refused to take it.
     */
    #[Override]
    public function forget(string $key): Wrote
    {
        unset($this->held[$key]);

        return Wrote::done(WhenAValueMayBeRead::WhileUnlocked);
    }

    /**
     * Seed a value, as though an earlier run had kept it.
     *
     * Separate from {@see keep()} so that a test arranging a starting state
     * cannot be mistaken for one exercising a write, and so that arranging one
     * works on a store that refuses every write — which is how a test reaches
     * *there is a record here and it cannot be read*.
     */
    public function alreadyHolding(string $key, string $value): self
    {
        $this->held[$key] = $value;

        return $this;
    }

    /** What is under one key, for a test to check what an adapter wrote. */
    public function whatIsUnder(string $key): ?string
    {
        return $this->held[$key] ?? null;
    }

    /**
     * Which keys this store is holding — for a test to check one per stack.
     *
     * @return list<string>
     */
    public function keysHeld(): array
    {
        return array_keys($this->held);
    }
}
