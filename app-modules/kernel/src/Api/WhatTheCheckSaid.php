<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the core said about a check, in the core's own words.
 *
 * Every finding shown must carry its code, its plain-language meaning and its
 * remedy — and "in the words the core produced" is the clause that makes this a
 * type rather than three fields a screen fills in. The app does not get to
 * paraphrase. A screen that wrote its own sentence for a check would be
 * inventing a diagnosis, and the operator would have no way to search for it or
 * to quote it to anybody.
 *
 * **All three arrived and all three were thrown away.** The doctor envelope
 * carries `code`, `meaning` and `remedies` on the verdict, and `Reports` read
 * the outcome tag and dropped the rest — deliberately, with a docblock saying
 * "the tag off the verdict, and nothing else it carries". So the words existed,
 * crossed the wire, and stopped at the reader. A screen could show that
 * something failed and nothing about what or what to do.
 *
 * **Three arms, because the wire has three situations and not two.** The
 * passing arm carries an optional note and no code, no meaning, no remedies.
 * The failing arms carry a whole problem. And `unverified` and `skipped` carry
 * a reason and no judgement at all — a check that could not run is its own
 * outcome rather than a level of severity, so that it is never mistaken for one
 * that passed, and an arm that folded it into either of the others would undo
 * that distinction here.
 *
 * A type with nullable fields would make a screen ask several questions to find
 * out which situation it is in, and the screen that asks all but one renders a
 * passing check as a failure with blank text.
 */
final readonly class WhatTheCheckSaid
{
    private function __construct(private WentWrong|CouldNotSay|null $said) {}

    /**
     * The check passed, so there is nothing to explain and nothing to do.
     *
     * No note is carried even though the wire offers one. A note on a passing
     * check is the core being chatty, and the rule is about what a finding must
     * carry when there is something wrong; reading it here would put text on a
     * screen that the requirement never asked for and that nothing pins.
     */
    public static function nothingWrong(): self
    {
        return new self(null);
    }

    /**
     * The check did not pass, and this is what the core said about it.
     *
     * The three travel together in a {@see WentWrong}, which is where the
     * blank-meaning refusal lives. Keeping them as fields here meant the
     * passing arm had to hold a blank one to keep the shape, and a value
     * nothing reads is a value no test can tell from any other.
     */
    public static function wentWrong(
        Code $code,
        string $meaning,
        Remedies $remedies,
        Severity $severity,
        Standing $standing,
        WhatItSaysUnderneath $underneath,
    ): self {
        return new self(WentWrong::of($code, $meaning, $remedies, $severity, $standing, $underneath));
    }

    /**
     * The check produced no verdict, and this is why.
     *
     * Both outcomes that reach here — `unverified` and `skipped` — arrive with
     * words this app read and threw away for as long as the docblock above
     * said the payload was dropped. See {@see CouldNotSay} for why they share
     * an arm and why the reason may not be blank.
     */
    public static function couldNotSay(string $reason, Remedies $remedies): self
    {
        return new self(CouldNotSay::of($reason, $remedies));
    }

    /**
     * @template TNothingWrong of object
     * @template TWentWrong of object
     * @template TCouldNotSay of object
     *
     * @param  Closure(): TNothingWrong  $nothingWrong
     * @param  Closure(Code, string, Remedies, Severity, Standing, WhatItSaysUnderneath): TWentWrong  $wentWrong
     * @param  Closure(string, Remedies): TCouldNotSay  $couldNotSay
     * @return TNothingWrong|TWentWrong|TCouldNotSay
     */
    public function either(Closure $nothingWrong, Closure $wentWrong, Closure $couldNotSay): object
    {
        // Read off the arms that carry something, the way `Kept` does. The
        // fall-through is the one arm holding nothing, so a situation this type
        // gains later cannot silently land on it — that would be a check with
        // no answer rendered as a check that found nothing wrong, which is the
        // one mistake `Conclusion` splits its cases to prevent.
        return match (true) {
            $this->said instanceof WentWrong => $wentWrong(
                $this->said->code(),
                $this->said->meaning(),
                $this->said->remedies(),
                $this->said->severity(),
                $this->said->standing(),
                $this->said->underneath(),
            ),
            $this->said instanceof CouldNotSay => $couldNotSay(
                $this->said->reason(),
                $this->said->remedies(),
            ),
            default => $nothingWrong(),
        };
    }
}
