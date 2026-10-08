<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UndoEnvelope;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Sdk\Internal\TheReversal;
use Modules\Sdk\Internal\Wire;

/**
 * The `undo` envelope, as what putting a run back came to.
 *
 * Opens the envelope and hands the report to {@see TheReversal}, which reads
 * it the same way wherever it arrives, a plugin install that did not hold
 * among them.
 */
final readonly class TheRunPutBack
{
    /**
     * What putting the run back came to.
     *
     * @param Envelope<mixed> $envelope the `undo` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ARunPutBack
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw UndoIsUnreadable::missing(WireField::Data);
        }

        return TheReversal::from($data);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return UndoEnvelope::in($envelope)->data;
    }
}
