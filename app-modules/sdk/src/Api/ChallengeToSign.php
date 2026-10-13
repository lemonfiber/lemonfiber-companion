<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Generated\DeviceChallengeEnvelope;
use Lemonfiber\Sdk\Generated\Kind;
use Lemonfiber\Sdk\KeyMint;
use Modules\Kernel\Api\AChallengeToSign;
use Modules\Kernel\Api\ATextToSign;
use Modules\Sdk\Api\Fields\DeviceChallengeField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** Reads the `device-challenge` envelope into the challenge, and the text a phone's key signs for this mint. */
final readonly class ChallengeToSign
{
    /**
     * The challenge the stack issued, with the text that proves this mint over it.
     *
     * @param Envelope<mixed> $envelope the `device-challenge` envelope, as the client returned it
     *
     * @throws ConfigurationProblem where the challenge holds a line feed, which no text can be signed over
     */
    public static function in(Envelope $envelope, KeyMint $mint): AChallengeToSign
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw MintingIsUnreadable::missing(Kind::DeviceChallenge, WireField::Data);
        }

        $challenge = Required::text($data, DeviceChallengeField::Challenge, MintingIsUnreadable::missing(Kind::DeviceChallenge, DeviceChallengeField::Challenge));

        return AChallengeToSign::of($challenge, ATextToSign::saying($mint->signedText($challenge)));
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
        return DeviceChallengeEnvelope::in($envelope)->data;
    }
}
