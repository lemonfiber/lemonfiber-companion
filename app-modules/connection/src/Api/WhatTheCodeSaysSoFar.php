<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Closure;
use InvalidArgumentException;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\PairingIsSpent;

use function trim;

/**
 * A pairing code the operator is typing, read as far as it will go.
 *
 * The value a screen renders from while somebody is still typing. It is built
 * fresh on every keystroke by {@see read()} rather than accumulated, so there is
 * no half-state to get out of step with the field — what the screen shows is a
 * function of what is in the box, which is also what makes it testable without a
 * screen.
 *
 * **The catch lives here, once.** {@see Pairing::read()} raises, and it is right
 * to: there is no half-paired stack to carry on with, and a caller acting on a
 * pairing that did not parse is the failure the refusal exists to stop. What
 * that leaves is a surface, where a refusal is not an error condition but the
 * ordinary state of a field somebody is halfway through filling in, arriving
 * once per keystroke. `C1` says the module boundary answers with an outcome, and
 * this is that boundary.
 *
 * **It is the only place a {@see FingerprintWasConfirmed} is made.** That is
 * the design rather than a convenience: `N1-R50` requires the operator to have
 * been *shown* the comparable form, and the thing that showed it is this. A
 * confirmation therefore cannot be constructed by a caller that never rendered
 * one — {@see confirmedByTheOperator()} is the only road, and it exists only in
 * the state where a form was on the screen.
 */
final readonly class WhatTheCodeSaysSoFar
{
    private function __construct(private ?Pairing $said, private WhereTheCodeGot $got) {}

    /**
     * How far a code gets, right now.
     *
     * Takes the route as well as the text because {@see Pairing::read()} does,
     * and for its reason: the two fail differently and the refusal says which.
     * Nothing here branches on it.
     *
     * A blank field answers `waiting` rather than `unreadable`. It is the state
     * a screen opens in, and it arrives again every time somebody clears the box
     * to start over — "that code is not readable" is a true sentence about an
     * empty string and a hostile one to show somebody who has typed nothing.
     *
     * **Two catches, because the remedies are opposite.**
     * `InvalidArgumentException` is the family every refusal along this road
     * belongs to — the payload was not material, or a half was missing, or the
     * address had no scheme, or the fingerprint was not sixty-four hex
     * characters — and each is answered by checking what was typed.
     * {@see PairingIsSpent} is caught first and answered differently: that code
     * was typed perfectly and `N1-R49` has expired it, so telling somebody to
     * check the characters sends them to look for a mistake that is not there.
     *
     * Neither catch is broad. `Throwable` here would absorb a misspelled method
     * in the kernel and render it as "check your code", which is `C6`'s own
     * example of the thing it refuses.
     */
    public static function read(string $said, HowItWasRead $how, Clock $clock): self
    {
        return trim($said) === '' ? self::waiting() : self::parsed($said, $how, $clock);
    }

    /** Nothing typed yet, or not enough of it to judge. */
    public static function waiting(): self
    {
        return new self(null, WhereTheCodeGot::Waiting);
    }

    /** It is not pairing material this app can read. */
    public static function unreadable(): self
    {
        return new self(null, WhereTheCodeGot::Unreadable);
    }

    /** It was pairing material, and it is past its moment (`N1-R49`). */
    public static function expired(): self
    {
        return new self(null, WhereTheCodeGot::Expired);
    }

    /** It parsed and is still good, so there is a fingerprint to compare. */
    public static function comparing(Pairing $said): self
    {
        return new self($said, WhereTheCodeGot::Comparing);
    }

    /** How far it got, for a screen deciding which sentence it owes. */
    public function got(): WhereTheCodeGot
    {
        return $this->got;
    }

    /**
     * The form the operator compares against what their stack displays.
     *
     * Empty where the code has not got that far, which is the conservative
     * direction and the only honest one: there is no fingerprint in a code that
     * did not parse, and a placeholder would be a string somebody could confirm.
     * A screen renders this under the `Comparing` branch and nowhere else, so
     * the empty case reaches no frame — but it has to be answerable, because a
     * method that raised would put a fatal one template edit away.
     */
    public function toCompare(): string
    {
        return $this->said instanceof Pairing
            ? AtAGlance::of($this->said->presenting())->shown()
            : '';
    }

    /**
     * The material itself, where the code got far enough to have any.
     *
     * The scanned road's way in, and it is separate from
     * {@see confirmedByTheOperator()} for the reason that method exists at all:
     * that one *mints* a {@see FingerprintWasConfirmed}, which may only exist
     * where a person compared something. A scanned code had nothing compared by
     * a person — `ADR-0018`'s whole point is that the digest arrived in the
     * payload, so the comparison happens in software — and reaching for the
     * confirming arm to get at the material would conjure the one value this
     * module is built to make unconjurable.
     *
     * So the two roads take two doors, and neither serves the other's purpose:
     * this one hands over no confirmation, and that one hands over nothing
     * without making one.
     *
     * @template TRead of object
     * @template TNotYet of object
     *
     * @param Closure(Pairing): TRead $read
     * @param Closure(): TNotYet      $notYet
     *
     * @return TRead|TNotYet
     */
    public function material(Closure $read, Closure $notYet): object
    {
        if (! $this->said instanceof Pairing) {
            return $notYet();
        }

        return $read($this->said);
    }

    /**
     * The operator says the form matches their stack.
     *
     * Both halves are handed to the caller together — the material and the
     * confirmation about it — so there is no way to end up holding a
     * confirmation and pairing something else with it. That is the mistake
     * {@see Introducing::confirmed()} refuses on the far side, and this is the
     * side that makes it hard to arrange in the first place.
     *
     * The refusing arm takes nothing, because there is nothing to say: a code
     * that has not reached the comparison has no fingerprint anybody could have
     * been shown. A screen reaches it only by offering the control outside the
     * branch that renders the form, which is a template mistake rather than an
     * operator one.
     *
     * @template TConfirmed of object
     * @template TNotYet of object
     *
     * @param Closure(Pairing, FingerprintWasConfirmed): TConfirmed $confirmed
     * @param Closure(): TNotYet                                    $notYet
     *
     * @return TConfirmed|TNotYet
     */
    public function confirmedByTheOperator(Closure $confirmed, Closure $notYet): object
    {
        if (! $this->said instanceof Pairing) {
            return $notYet();
        }

        return $confirmed(
            $this->said,
            FingerprintWasConfirmed::byTheOperator(AtAGlance::of($this->said->presenting())),
        );
    }

    /**
     * What a non-empty code turns out to be.
     *
     * Split from {@see read()} rather than written inline, because the two ask
     * different questions: whether anything was typed at all, and what it says
     * if something was. Keeping them together also put four returns in one
     * method, which SonarCloud reports and which is the same observation from
     * the other side — a method with four exits is usually two methods.
     */
    private static function parsed(string $said, HowItWasRead $how, Clock $clock): self
    {
        try {
            return self::comparing(Pairing::read($said, $how, $clock));
        } catch (PairingIsSpent) {
            return self::expired();
        } catch (InvalidArgumentException) {
            return self::unreadable();
        }
    }
}
