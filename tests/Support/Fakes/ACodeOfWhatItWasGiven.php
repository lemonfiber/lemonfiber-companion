<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Encoding;

/**
 * An encoder that draws the same small square for any address, and remembers the address.
 *
 * What a screen test needs of a code is that one is drawn, and drawn of the
 * address the stack sent; the squares a real encoder makes are the contract
 * test's business. One that draws nothing stands in for text too long to fit.
 *
 * Not `readonly`: what it was handed is written when the handing happens.
 */
final class ACodeOfWhatItWasGiven implements Encoding
{
    private ?AnAddressToHand $given = null;

    private function __construct(private readonly AScannableCode $drawn) {}

    /** An encoder that draws a small square. */
    public static function working(): self
    {
        return new self(AScannableCode::drawn('101', '010', '101'));
    }

    /** One that cannot draw what it is given. */
    public static function drawingNothing(): self
    {
        return new self(AScannableCode::none());
    }

    /** The address it was last handed, or nothing where it never was. */
    public function given(): ?AnAddressToHand
    {
        return $this->given;
    }

    public function codeFor(AnAddressToHand $address): AScannableCode
    {
        $this->given = $address;

        return $this->drawn;
    }
}
