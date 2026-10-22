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
    /** Why the operator's own overlay, beside any envelope that carries it, is not read. */
    public const string THE_OPERATORS_OVERLAY = 'The operator\'s own overlay and every service it changes, which lemonfiber merges over the project and vets nothing of. It is written at the machine and is the operator\'s, and no requirement asks the app to show it.';

    /**
     * Why `rehearsed` goes unread on every report this app never asks to rehearse.
     *
     * An action this app rehearses before its yes has its `rehearsed` read where
     * its report is read. Every other action is sent without `dry_run`, so its
     * report says `false` wherever this app reads it.
     */
    public const string NEVER_ASKED_FOR_A_REHEARSAL = 'Whether the report was a rehearsal. This app sends this action without `dry_run`, so the answer is always that it was not.';

    /**
     * Every row of the register.
     *
     * @return list<array{path: string, because: string}>
     */
    public static function rows(): array
    {
        return [...UnreadOnEnvelopesAToL::ROWS, ...UnreadOnEnvelopesMToZ::ROWS, ...UnreadOnThePluginsEnvelope::ROWS, ...UnreadOnThePluginRollbacks::ROWS];
    }
}
