<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Every path this app has read and decided not to read, and why.
 *
 * `path` names one place on one envelope exactly, and covers everything beneath
 * it. `because` is the decision, and it is the only part that survives the
 * person who made it.
 */
final readonly class WhatThisAppDoesNotRead
{
    /**
     * Why `rehearsed` goes unread on every report this app never asks to rehearse.
     *
     * The one rehearsal this app asks for is a verb's, before its yes, and that
     * report is a lifecycle, whose `rehearsed` is read. Every other action is sent
     * without `dry_run`, so its report says `false` wherever this app reads it.
     */
    public const string NEVER_ASKED_FOR_A_REHEARSAL = 'Whether the report was a rehearsal. This app sends this action without `dry_run`, so the answer is always that it was not; the one rehearsal it asks for is a verb\'s, read from the lifecycle report.';

    /**
     * Every row of the register.
     *
     * @return list<array{path: string, because: string}>
     */
    public static function rows(): array
    {
        return [...UnreadOnEnvelopesAToL::ROWS, ...UnreadOnEnvelopesMToZ::ROWS, ...UnreadOnThePluginsEnvelope::ROWS];
    }
}
