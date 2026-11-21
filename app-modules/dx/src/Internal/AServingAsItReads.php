<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function is_array;

use Modules\Sdk\Api\WireField;

/**
 * How a stand-in stack says it is served, as a reader can take it: presenting the certificate its phones pin.
 *
 * The `served` envelope's declaration types a fingerprint only as text, and a
 * synthesised word is no SHA-256 digest; the certificate is given the pin a
 * stand-in stack pairs with.
 */
final readonly class AServingAsItReads
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope): array
    {
        $data = is_array($envelope['data']) ? $envelope['data'] : [];
        $under = WireField::Certificate->value;

        if (array_key_exists($under, $data) && is_array($data[$under])) {
            $data[$under] = [...$data[$under], WireField::Fingerprint->value => PairingMaterialAsItReads::thePin()];
        }

        $envelope['data'] = $data;

        return $envelope;
    }
}
