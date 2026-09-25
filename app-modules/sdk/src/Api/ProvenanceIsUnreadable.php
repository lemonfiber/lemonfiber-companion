<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `provenance` envelope did not hold what the contract says it holds.
 *
 * {@see HistoryIsUnreadable}'s refusal, for where the services come from, and
 * for its reason: every one of these is a bug somewhere other than here, and
 * the message names the field, the row and what arrived because that is what
 * shortens the search. A developer reads it, so it is `sprintf` and never
 * translated (`L1`).
 *
 * **A list of origins is refused rather than salvaged.** A service dropped for
 * being unreadable is a service the screen says this stack does not run — and
 * the one somebody went looking for is as likely to be that one as any.
 */
final class ProvenanceIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The provenance envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function service(int $position): self
    {
        return new self(sprintf(
            'Service %d in the provenance envelope is not a service. It is refused rather than dropped: a list one row short says this stack does not run something it does.',
            $position,
        ));
    }

    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Service %d in the provenance envelope has no readable `%s`. An origin that will not say what it is reads as complete to somebody deciding what to trust.',
            $position,
            $field->value,
        ));
    }

    /**
     * One service arrived twice.
     *
     * Refused here rather than left to the kernel, whose refusal the adapter
     * does not turn into an obstacle — it would reach a screen as an uncaught
     * raise.
     */
    public static function twice(string $service, int $position): self
    {
        return new self(sprintf(
            'Service %d in the provenance envelope is `%s` again. A stack names each service once, and one service with two origins is a question with two answers.',
            $position,
            $service,
        ));
    }
}
