<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function intdiv;
use function is_array;

use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;
use Modules\Sdk\Api\Fields\PairingField;

use function str_repeat;

/**
 * A stand-in payload whose pairing material carries a fingerprint that reads,
 * with the check code a phone works out from it.
 *
 * The declaration types the fingerprint as text, and a synthesised one is the
 * word `Fingerprint`, which no reader takes for a certificate's digest; a check
 * code that is the word `Compare` is one no phone would match. Corrected
 * wherever a payload carries material, with the stand-in machine's own pin,
 * and left as it was everywhere else.
 */
final readonly class PairingMaterialAsItReads
{
    /** The payload, corrected where it carries pairing material. */
    public static function in(mixed $data): mixed
    {
        $under = PairingField::Material->value;

        if (! is_array($data) || ! array_key_exists($under, $data) || ! is_array($data[$under])) {
            return $data;
        }

        $pin = str_repeat('ab', intdiv(Fingerprint::CHARACTERS, 2));

        return [
            ...$data,
            $under => [...$data[$under], PairingField::Fingerprint->value => $pin],
            PairingField::Compare->value => AtAGlance::of(Fingerprint::of($pin))->shown(),
        ];
    }
}
