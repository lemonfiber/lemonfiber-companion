<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the core said about a check, in the core's own words (`N2-R3`).
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
 * **Two arms, because a check that passed has none of it.** The wire says so
 * too: the passing arm of the verdict carries an optional note and no code, no
 * meaning, no remedies. A type with three nullable fields would make a screen
 * ask three questions to find out which situation it is in, and the screen that
 * asks two of them renders a passing check as a failure with blank text.
 */
final readonly class WhatTheCheckSaid
{
    private function __construct(private ?WentWrong $wrong) {}

    /**
     * The check passed, so there is nothing to explain and nothing to do.
     *
     * No note is carried even though the wire offers one. A note on a passing
     * check is the core being chatty, and `N2-R3` is about what a finding must
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
    ): self {
        return new self(WentWrong::of($code, $meaning, $remedies, $severity, $standing));
    }

    /**
     * @template TNothingWrong of object
     * @template TWentWrong of object
     *
     * @param  Closure(): TNothingWrong  $nothingWrong
     * @param  Closure(Code, string, Remedies, Severity, Standing): TWentWrong  $wentWrong
     * @return TNothingWrong|TWentWrong
     */
    public function either(Closure $nothingWrong, Closure $wentWrong): object
    {
        // Read off the failure, the way `Kept` does: the arm with something to
        // explain is the one the type is written around, and a fall-through is
        // how a branch becomes the one nobody tested.
        return $this->wrong instanceof WentWrong
            ? $wentWrong(
                $this->wrong->code(),
                $this->wrong->meaning(),
                $this->wrong->remedies(),
                $this->wrong->severity(),
                $this->wrong->standing(),
            )
            : $nothingWrong();
    }
}
