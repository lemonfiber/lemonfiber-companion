<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use function json_decode;
use function json_encode;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhereAStreamResumes;
use Modules\Kernel\Api\WhereHearingLeftOff;
use Modules\Vault\Internal\KeptInAShape;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\TheEventAsWritten;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

/**
 * The last event each stack's stream carried, kept in the platform's own store.
 *
 * **The keychain rather than a file, for {@see PlatformStacks}' reason.** An
 * event's identifier is not a secret, and it is still a note of when this
 * phone last heard a machine the same store holds the address of.
 *
 * **A key per stack**, as {@see PlatformWorkLeftRunning} keeps one per stack:
 * a stream writes where it got to on every wake it moved, so one key each is
 * what lets that be written without reading and rewriting every other
 * stack's.
 *
 * **A shape number, because everything retained carries one**, and a value in
 * a shape this build does not recognise reads as nothing kept. That costs one
 * stream opened from the start, which hands back no past alert.
 */
final readonly class PlatformWhereHearingLeftOff implements WhereHearingLeftOff
{
    /** The shape this build writes, and the only one it reads. */
    private const int SHAPE = 1;

    public function __construct(private Keeps $store, private Stacks $stacks) {}

    public function heardTo(StackId $stack, AnEventHeard $event): bool
    {
        $written = json_encode(KeptInAShape::written(self::SHAPE, TheEventAsWritten::written($event)));

        if ($written === false) {
            return false;
        }

        return $this->store->keep($this->keyFor($stack), $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
        )->held;
    }

    public function leftOffAt(StackId $stack): WhereAStreamResumes
    {
        return $this->store->read($this->keyFor($stack))->either(
            found: static fn(string $written): WhereAStreamResumes => TheEventAsWritten::read(json_decode($written, associative: true), self::SHAPE),
            nothing: static fn(): WhereAStreamResumes => WhereAStreamResumes::fromTheStart(),
            refused: static fn(): WhereAStreamResumes => WhereAStreamResumes::fromTheStart(),
        );
    }

    /**
     * Let go of where every stack paired here left off.
     *
     * The store cannot list what it holds, so each is found under a stack this
     * phone is paired with.
     */
    public function forgetEverything(): Forgotten
    {
        $forgotten = Forgotten::nothing();

        foreach ($this->stacks->configured() as $stack) {
            $forgotten = $forgotten->beside($this->forgetOne($stack->id()));
        }

        return $forgotten;
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->forgetOne($stack);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->read($this->keyFor($stack))->either(
            found: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    /** Let go of where this stack's stream left off, counting it where it was kept. */
    private function forgetOne(StackId $stack): Forgotten
    {
        $key = $this->keyFor($stack);

        return $this->store->read($key)->either(
            found: fn(): Forgotten => $this->store->forget($key)->either(
                done: static fn(): Forgotten => Forgotten::rows(1),
                refused: static fn(): Forgotten => Forgotten::nothing(),
            ),
            nothing: static fn(): Forgotten => Forgotten::nothing(),
            refused: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    private function keyFor(StackId $stack): string
    {
        return KeptUnder::WhereHearingLeftOff->beneath($stack->stored());
    }
}
