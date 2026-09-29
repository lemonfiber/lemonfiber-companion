<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Sealing;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Unsealing;
use Modules\Kernel\Api\WhyNothingIsSealed;

use function spl_object_id;
use function sprintf;

/**
 * A seal that encrypts nothing and keeps every promise the real one makes.
 *
 * A payload here is a receipt: a word naming this seal and a count, with the
 * value kept beside it in memory. That is enough to be held to the whole of
 * {@see Sealed}'s contract — a payload opens back to its value, two seals of
 * one value differ, a payload altered or sealed by another phone does not
 * open, and a stack's hash is stable, distinct and not its identity — without
 * a cipher in the suite of every owner that seals something.
 *
 * **The seal is named in every payload** so that one sealed by another
 * instance is foreign here, as one sealed under another key is to the adapter.
 *
 * **Two keys, made by whatever asks for each first**, as the adapter's are:
 * sealing and opening make the data key where there is none, hashing a stack
 * makes the stack key, asking for the standing makes both, and only the
 * standing says so.
 */
final class ASealInMemory implements Sealed
{
    /** @var array<string, string> payload => the value sealed in it */
    private array $sealed = [];

    /** @var array<string, string> stack identity => its hash */
    private array $stacks = [];

    /** @var array<string, true> the case name of each key this seal holds */
    private array $keys = [];

    private int $seals = 0;
    private int $unkept = 0;

    private function __construct(private readonly ?WhyNothingIsSealed $refusing) {}

    /** Secure storage that works, holding no key yet: a first launch. */
    public static function working(): self
    {
        return new self(null);
    }

    /** A device with no secure storage at all. */
    public static function withNoSecureStorage(): self
    {
        return new self(WhyNothingIsSealed::NoSecureStorage);
    }

    /** Secure storage that is there and will not open. */
    public static function thatWillNotOpen(): self
    {
        return new self(WhyNothingIsSealed::KeyUnreadable);
    }

    public function standing(): SealStanding
    {
        if ($this->refusing instanceof WhyNothingIsSealed) {
            return SealStanding::Unavailable;
        }

        return $this->standingOf(SealKey::TheDataKey)->beside($this->standingOf(SealKey::TheStackKey));
    }

    public function seal(Unsealed $value): Sealing
    {
        if ($this->refusing instanceof WhyNothingIsSealed) {
            return Sealing::refused($this->refusing);
        }

        $this->madeNow(SealKey::TheDataKey);
        $this->seals++;
        $payload = sprintf('sealed-by-%d-%d', spl_object_id($this), $this->seals);
        $this->sealed[$payload] = $value->inTheClear();

        return Sealing::sealed(SealedPayload::of($payload));
    }

    public function open(SealedPayload $payload): Unsealing
    {
        if ($this->refusing instanceof WhyNothingIsSealed || $this->madeNow(SealKey::TheDataKey)) {
            return Unsealing::unreadable();
        }

        return array_key_exists($payload->forTheStore(), $this->sealed)
            ? Unsealing::opened(Unsealed::of($this->sealed[$payload->forTheStore()]))
            : Unsealing::unreadable();
    }

    public function stack(StackId $stack): SealedStack
    {
        if ($this->refusing instanceof WhyNothingIsSealed) {
            $this->unkept++;

            return SealedStack::of(sprintf('kept-nowhere-%d', $this->unkept));
        }

        $this->madeNow(SealKey::TheStackKey);

        if (! array_key_exists($stack->stored(), $this->stacks)) {
            $this->stacks[$stack->stored()] = sprintf('stack-%d', count($this->stacks) + 1);
        }

        return SealedStack::of($this->stacks[$stack->stored()]);
    }

    /**
     * Both keys go, as they do on a device restored from a backup: for a test to arrange.
     *
     * What was sealed under them goes with them, which is what a new key means.
     */
    public function losesItsKeys(): self
    {
        $this->keys = [];
        $this->sealed = [];
        $this->stacks = [];

        return $this;
    }

    private function standingOf(SealKey $which): SealStanding
    {
        return $this->madeNow($which) ? SealStanding::MadeAfresh : SealStanding::Held;
    }

    /** Make one key if there is none, and say whether that happened now. */
    private function madeNow(SealKey $which): bool
    {
        if (array_key_exists($which->name, $this->keys)) {
            return false;
        }

        $this->keys[$which->name] = true;

        return true;
    }
}
