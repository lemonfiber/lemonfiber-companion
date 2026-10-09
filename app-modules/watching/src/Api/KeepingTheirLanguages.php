<?php

declare(strict_types=1);

namespace Modules\Watching\Api;

use Closure;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Whose;
use Modules\Watching\Internal\LanguagesKept;
use Modules\Watching\Internal\TheirLanguagesAsKept;

/**
 * What a member chose to hear and read titles in on this phone, decided here.
 *
 * **Asked by stack, for whoever is signed in to it**, so a screen offering the
 * choice resumes no session of its own; the player asks for the member it
 * already holds.
 *
 * **One choice per stack, for the member signed in to it**, sealed before the
 * store sees it with whose it is inside the seal. The house is never told: it
 * is a setting of the phone in the member's hand.
 *
 * **Nothing chosen is a title as it comes**: its own sound, no subtitles. So is
 * a choice another member made on the same house, one that does not open, and
 * one in a shape this build does not read; the last two are let go of.
 *
 * **Forgotten with the stack**, as everything the phone keeps of it is.
 */
final readonly class KeepingTheirLanguages implements ForgetsAStack
{
    public function __construct(
        private Sealed $seal,
        private LanguagesKept $kept,
        private Clock $clock,
        private SecureStorage $storage,
    ) {}

    /** What whoever is signed in to this stack chose, or a title as it comes where nobody is or they chose nothing. */
    public function chosenOn(StackId $stack): TheirLanguages
    {
        return $this->storage->resume($stack)->whoseItIs(
            nobody: static fn(): TheirLanguages => TheirLanguages::asTheTitleComes(),
            theirs: fn(Whose $whose): TheirLanguages => $this->of($stack, $whose),
        );
    }

    /** Whoever is signed in to this stack hears titles in this language from now on. */
    public function hearIn(StackId $stack, HearIn $hear): Noted
    {
        return $this->changing($stack, static fn(TheirLanguages $now): TheirLanguages => $now->hearing($hear));
    }

    /** Whoever is signed in to this stack reads subtitles in this language from now on, or none. */
    public function readIn(StackId $stack, ReadIn $read): Noted
    {
        return $this->changing($stack, static fn(TheirLanguages $now): TheirLanguages => $now->reading($read));
    }

    /** Keep what this member chose on this stack, replacing what was chosen before. */
    public function choose(StackId $stack, Whose $whose, TheirLanguages $chosen): Noted
    {
        $written = TheirLanguagesAsKept::written($whose, $chosen);

        if (! $written instanceof Unsealed) {
            return Noted::notKept();
        }

        return $this->seal->seal($written)->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }

    /** What this member chose on this stack, or a title as it comes where they chose nothing. */
    public function of(StackId $stack, Whose $whose): TheirLanguages
    {
        $sealed = $this->seal->stack($stack);

        return $this->kept->newest($sealed)->either(
            found: fn(SealedPayload $payload, Shape $shape): TheirLanguages => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): TheirLanguages => TheirLanguagesAsKept::read($shape, $value, $whose) ?? TheirLanguages::asTheTitleComes(),
                unreadable: fn(): TheirLanguages => $this->discarded($sealed),
            ),
            none: static fn(): TheirLanguages => TheirLanguages::asTheTitleComes(),
            unreadable: fn(): TheirLanguages => $this->discarded($sealed),
        );
    }

    /** Let go of the choice kept for this stack. */
    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->kept->forget($this->seal->stack($stack));
    }

    /**
     * Whether a choice is kept for this stack, or might be.
     *
     * Where the seal's keys cannot be read, a stack's hash matches no row, so
     * the store cannot say; that is answered as might be, and a removal waits
     * for the keys rather than leaving a choice behind it.
     */
    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->newest($this->seal->stack($stack))->holdsARow();
    }

    /**
     * Change what whoever is signed in to this stack chose, and keep it; nothing is kept where nobody is.
     *
     * @param Closure(TheirLanguages): TheirLanguages $change
     */
    private function changing(StackId $stack, Closure $change): Noted
    {
        return $this->storage->resume($stack)->whoseItIs(
            nobody: static fn(): Noted => Noted::notKept(),
            theirs: fn(Whose $whose): Noted => $this->choose($stack, $whose, $change($this->of($stack, $whose))),
        );
    }

    private function discarded(SealedStack $sealed): TheirLanguages
    {
        $this->kept->forget($sealed);

        return TheirLanguages::asTheTitleComes();
    }
}
