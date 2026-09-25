<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;

use function sprintf;

/**
 * The `status` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see StuckIsUnreadable} is, for the roster's payload, and for
 * its reason: every one of these is a bug somewhere other than here, and the
 * message names the field and what arrived because that is the only thing that
 * shortens the search. A developer reads it, so it is `sprintf` and never
 * translated (`L1`).
 *
 * **A listing one row short is the listing a verb is chosen from.** That is
 * sharper here than anywhere else this refusal has a sibling: an operator picks
 * a row and says *stop*, so a row dropped for being unreadable is not a gap in
 * a report but a service nobody can turn off from the phone — and a row whose
 * `id` was salvaged into something plausible is a verb sent about the wrong
 * thing entirely.
 *
 * **The accepted words come from the enums.** Three of these messages list what
 * this app reads, and each list is built from `cases()` rather than written out,
 * so a word added to the contract cannot leave a message describing the
 * vocabulary of the build before it.
 */
final class RosterIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The status envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function item(int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope is not a service. It is refused rather than dropped: a listing one row short is a service an operator cannot start, stop or restart, and nothing on the screen would say so.',
            $position,
        ));
    }

    public static function said(WireField $field, int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope has no readable `%s`. A row missing it is a row an operator cannot act on, and showing it anyway offers a verb about a blank.',
            $position,
            $field->value,
        ));
    }

    public static function leaning(int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope names something in `%s` that is not a service. What leans on a service is the sentence `N2-R8` puts in front of stopping it, so a name that cannot be read would understate what a stop disturbs.',
            $position,
            WireField::DependsOn->value,
        ));
    }

    public static function ended(int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope carries an `%s` that is neither absent, empty, nor a number. It is refused rather than read as still running, which is the reading that turns a service that died into one nobody looks at.',
            $position,
            WireField::Exit->value,
        ));
    }

    public static function condition(string $said): self
    {
        return new self(sprintf(
            'The status envelope says the stack is `%s`, and this app reads %s. A word this build has not heard of means the contract moved, and the nearest guess would be the reassuring one.',
            $said,
            self::accepting(array_map(
                static fn(HowTheStackIsRunning $running): string => $running->value,
                HowTheStackIsRunning::cases(),
            )),
        ));
    }

    public static function running(string $said, int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope says it is `%s`, and this app reads %s. Guessing which was meant is how a service that is crash-looping gets drawn as one that is running.',
            $position,
            $said,
            self::accepting(array_map(
                static fn(HowAServiceRuns $runs): string => $runs->value,
                HowAServiceRuns::cases(),
            )),
        ));
    }

    public static function matters(string $said, int $position): self
    {
        return new self(sprintf(
            'Service %d in the status envelope says it is `%s`, and this app reads %s. How much a service matters is what decides how loudly a stop is asked about, so an unrecognised word must not become the quietest one.',
            $position,
            $said,
            self::accepting(array_map(
                static fn(HowMuchItMatters $matters): string => $matters->value,
                HowMuchItMatters::cases(),
            )),
        ));
    }

    /**
     * The words this app reads, as a sentence.
     *
     * One place rather than three, so the three messages that end in a list
     * cannot drift apart in how they punctuate one.
     *
     * @param list<string> $words
     */
    private static function accepting(array $words): string
    {
        return implode(', ', array_map(
            static fn(string $word): string => sprintf('`%s`', $word),
            $words,
        ));
    }
}
