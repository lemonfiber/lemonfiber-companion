<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\WireVersion;

/**
 * The one gate every answer passes before anything reads it.
 *
 * `N1-R13` refuses an envelope whose wire version this app does not support.
 * Here rather than in each translator, because "each translator checks" is the
 * arrangement that holds until somebody adds the third one — and the failure of
 * the missing check is not an error, it is a field read out of a payload whose
 * meaning changed, handed to a screen as a fact.
 *
 * **It answers with the envelope it was given.** A guard that returned nothing
 * would be a statement a reader can delete without the line beneath it
 * changing; written this way, `self::payload(Wire::checked($envelope))` cannot
 * be shortened without removing the check on purpose.
 *
 * The version is checked and then dropped. Nothing downstream branches on it,
 * because there is one version — and the day there are two, the translator that
 * needs to know will ask {@see WireVersion} rather than re-derive it from an
 * integer.
 */
final readonly class Wire
{
    /**
     * @template TData
     *
     * @param Envelope<TData> $envelope
     * @return Envelope<TData>
     */
    public static function checked(Envelope $envelope): Envelope
    {
        WireVersion::tryFrom($envelope->apiVersion)
            ?? throw EnvelopeIsNotRead::inVersion($envelope->apiVersion);

        return $envelope;
    }
}
