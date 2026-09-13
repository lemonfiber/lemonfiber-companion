<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * A repair the stack is offering, and the three things it has to be offered with.
 *
 * `N2-R4` is one sentence with three clauses in it: where the core offers a
 * repair the app must offer it, and must state **what it does**, **what else it
 * affects**, and **whether it can be undone**, before asking for confirmation.
 * The contract sends all three — `repair.offered[]` is
 * `{check, does, effects, reversible}` — so this is not a requirement about
 * finding something out. It is a requirement about not dropping it.
 *
 * Which is how it gets broken. Nobody decides to offer a repair without saying
 * what it affects; a screen is written around the one field that reads like the
 * label, `does`, and the other two are in the envelope somewhere and never make
 * it to the template. That failure is invisible in review — the screen looks
 * finished — and it is only wrong at the moment an operator agrees to something
 * permanent believing it was not.
 *
 * So there is no `does()`. {@see self::stated()} is the only way to reach any of
 * it, and it hands over all three together: a screen that shows what a repair
 * does without saying what it affects has been given both and thrown one away,
 * which is a thing somebody has to have written on purpose. This is the
 * argument {@see Reading} makes about a timestamp, applied to a consequence.
 *
 * **A `Repair` is not a {@see Remedy}.** A remedy is what the doctor tells the
 * operator to go and do — `{action, detail}`, prose, carried out by a person.
 * A repair is something the stack will do itself if it is told to, and the
 * three extra facts exist because agreeing to it is agreeing to let it act. The
 * two arrive in different envelopes and the app must not let one stand in for
 * the other: offering a remedy needs no confirmation, and offering a repair
 * needs {@see Confirmed}.
 */
final readonly class Repair
{
    private function __construct(
        private string $check,
        private string $does,
        private Effects $effects,
        private Undoing $undoing,
    ) {}

    /**
     * The one place a repair the stack offered becomes one this app can offer.
     *
     * Every clause of `N2-R4` is a parameter, so a repair missing one cannot be
     * built rather than being built and rendered short. `$undoing` is an enum
     * rather than the wire's boolean because a bare `true` at a call site says
     * nothing about which way round it goes (`D5`) and, more to the point, has
     * no word an operator can read (`L2`).
     *
     * A blank `does` is refused for {@see Remedy}'s reason: it renders as a
     * button with no label, and a button with no label above a list of
     * consequences is the worst version of this screen.
     */
    public static function offered(
        string $check,
        string $does,
        Effects $effects,
        Undoing $undoing,
    ): self {
        $trimmed = trim($does);

        if ($trimmed === '') {
            throw RepairSaysNothing::itDoes();
        }

        if (trim($check) === '') {
            throw RepairSaysNothing::whatItIsAbout();
        }

        return new self(trim($check), $trimmed, $effects, $undoing);
    }

    /**
     * Which check this repair answers, for matching it back to a finding.
     *
     * Published on its own where the other three are not, and the asymmetry is
     * the point: this one is not something the operator reads. It is how a
     * screen knows which finding a repair belongs under, and a screen that has
     * it has learned nothing about what the repair would do.
     */
    public function answers(): string
    {
        return $this->check;
    }

    /**
     * Say all three, and get whatever saying them produced.
     *
     * One closure rather than three accessors. `N2-R4`'s three clauses are one
     * requirement, and three getters are three chances to call two of them.
     *
     * @template TSaid of object
     *
     * @param Closure(string, Effects, Undoing): TSaid $say
     *
     * @return TSaid
     */
    public function stated(Closure $say): object
    {
        return $say($this->does, $this->effects, $this->undoing);
    }
}
