<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\HowFarItGoesBack;

use function sprintf;

/**
 * The `history` envelope did not hold what the contract says it holds.
 *
 * {@see HostingIsUnreadable}'s refusal, for the record, and for its reason:
 * every one of these is a bug somewhere other than here, and the message names
 * the field, the row and what arrived because that is what shortens the
 * search. A developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **This record is refused rather than salvaged, and the direction of error is
 * the argument.** A row dropped for being unreadable is a change the screen
 * says did not happen. That is the one wrong answer a record can give that
 * nobody goes and checks: the operator came here to find out what was done
 * behind their back, and a shorter list is what they hoped to see.
 */
final class HistoryIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The history envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function change(int $position): self
    {
        return new self(sprintf(
            'Change %d in the history envelope is not a change. It is refused rather than dropped: a record one row short says something did not happen that did.',
            $position,
        ));
    }

    public static function said(WireField $field, int $position): self
    {
        return new self(sprintf(
            'Change %d in the history envelope has no readable `%s`. A record row that admits something was done and will not say what is worse than no row.',
            $position,
            $field->value,
        ));
    }

    public static function reversal(string $said, int $position): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added cannot leave this message describing
        // the old vocabulary.
        return new self(sprintf(
            'Change %d in the history envelope says it goes back `%s`, and this app reads %s. Guessing which was meant is how a change that cannot be undone gets shown as one that can.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(HowFarItGoesBack $reversal): string => sprintf('`%s`', $reversal->value),
                HowFarItGoesBack::cases(),
            )),
        ));
    }

    /**
     * When a change was made did not arrive as the stack writes it.
     *
     * Seconds since the epoch, as digits, is what the core stamps a change
     * with and what the contract says. Anything else — an ISO date, a signed
     * number, digits too long to be a moment — is refused rather than guessed
     * at, because a guessed moment is a record shown out of the order it
     * happened in.
     */
    public static function at(string $said, int $position): self
    {
        return new self(sprintf(
            'Change %d in the history envelope says it was made at `%s`, and this app reads seconds since the epoch, written as digits. Guessing what else was meant is how a record gets shown out of the order it happened in.',
            $position,
            $said,
        ));
    }

    /**
     * A change said what to do instead, and not why putting it back stopped.
     *
     * The stack builds both from one refusal to go further, and that refusal
     * always has a reason. A suggestion with none did not come from there, and
     * shown alone it tells an operator to go and do something without saying
     * what it would fix.
     */
    public static function insteadWithoutReason(int $position): self
    {
        return new self(sprintf(
            'Change %d in the history envelope says what to do instead and not why putting it back stops short. The stack gives the two together, with the reason always present, so a suggestion on its own did not come from a refusal to go further.',
            $position,
        ));
    }

    /**
     * The count of what came with a change was not a count this app can show.
     *
     * Its own sentence because the count includes the change it is on, so
     * anything below one is not a small number but an impossible one — and
     * the row carrying it is the row that gets undone by itself.
     */
    public static function alongside(int $position): self
    {
        return new self(sprintf(
            'Change %d in the history envelope does not say how many changes came with it as a whole number of at least one. The count includes the change itself, and undoing one line of a larger operation leaves a machine in a state nobody chose.',
            $position,
        ));
    }
}
