<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\JobEnvelope;
use Modules\Kernel\Api\Job;
use Modules\Sdk\Api\Fields\JobField;
use Modules\Sdk\Internal\Wire;

/**
 * The `job` envelope, as the name work already begun is asked about by.
 *
 * One reader for it rather than one per action, because the envelope belongs to
 * none of them: an action that reaches the container engine or the services
 * runs for minutes, so it is answered with a name and the outcome arrives
 * through that name later. A repair, a start, a stop and a restart differ in
 * what finally comes back and not at all in this.
 *
 * **The `action` field is deliberately not read.** It says which action was
 * acknowledged, and the caller already knows — it is the one that just asked.
 * Reading it to check would be this app telling a stack what it had been asked,
 * and reading it to decide would be a second source of truth for something the
 * call site is holding.
 *
 * Written the way {@see Rosters} and {@see Stoppages} are: a static fold with
 * no state, reading through {@see WireField}, refusing rather than salvaging.
 */
final readonly class Handles
{
    /**
     * The handle a stack answered an action with.
     *
     * @param Envelope<mixed> $envelope the `job` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Job
    {
        $data = self::handedBack(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HandleIsUnreadable::missing(WireField::Data);
        }

        return Job::named(self::text($data, JobField::Job));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Stoppages::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and an
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function handedBack(Envelope $envelope): mixed
    {
        return JobEnvelope::in($envelope)->data;
    }

    /**
     * A named field, as text.
     *
     * Blank is not refused here. A name present and empty is the one state
     * there is no answer for — the action was delivered and there is
     * nothing to ask after it by — and {@see Job::named()} is where that is
     * said, one layer further in, so it is said once for every caller.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw HandleIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said)) {
            throw HandleIsUnreadable::missing($field);
        }

        return $said;
    }
}
