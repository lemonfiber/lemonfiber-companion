<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Waiting;

use function sprintf;

/**
 * The `household` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see ReportIsUnreadable} is, for the payload the household
 * screen reads, and for its reason: every one of these is a bug somewhere other
 * than here, and the message names the field and what arrived because that is
 * the only thing that shortens the search. A developer reads it, so it is
 * `sprintf` and never translated (`L1`).
 *
 * **A household is worth refusing rather than salvaging**, and more sharply
 * than a report is. A report one finding short reads as a stack with one fewer
 * problem; a household one request short reads as somebody never having asked.
 * The person who asked is in the house, and they will ask again — of the
 * operator, who has been shown a screen saying there is nothing to decide.
 */
final class HouseholdIsUnreadable extends InvalidArgumentException
{
    public static function missing(WireField $field): self
    {
        return new self(sprintf(
            'The household envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function member(int $position): self
    {
        return new self(sprintf(
            'Member %d in the household envelope is not a member. A household with a row this app cannot read is refused rather than shown one person short, because a missing member takes everything they asked for with them.',
            $position,
        ));
    }

    public static function request(string $by, int $position): self
    {
        return new self(sprintf(
            'Request %d belonging to `%s` is not a request. It is refused rather than dropped: a request missing from the list reads as one nobody ever made, and the person who made it will ask again.',
            $position,
            $by,
        ));
    }

    public static function refusal(string $by, int $position): self
    {
        return new self(sprintf(
            'Request %d belonging to `%s` says it was declined and carries no readable reason. `D7-R7` makes the reason part of declining, so this is refused rather than shown as a word nobody can explain to the person who asked.',
            $position,
            $by,
        ));
    }

    public static function standing(string $said): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added to the contract cannot leave this
        // message describing the old vocabulary.
        return new self(sprintf(
            'The household envelope says a request is `%s`, and this app reads %s. Guessing which was meant is how something already here gets offered for approval.',
            $said,
            implode(', ', array_map(
                static fn(Waiting $standing): string => sprintf('`%s`', $standing->value),
                Waiting::cases(),
            )),
        ));
    }
}
