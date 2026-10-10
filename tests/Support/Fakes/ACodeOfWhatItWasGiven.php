<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Encoding;

/**
 * An encoder that draws the same small square for any text, and remembers the text.
 *
 * What a screen test needs of a code is that one is drawn, and drawn of what
 * the stack sent; the squares a real encoder makes are the contract
 * test's business. One that draws nothing stands in for text too long to fit.
 *
 * Not `readonly`: what it was handed is written when the handing happens.
 */
final class ACodeOfWhatItWasGiven implements Encoding
{
    private AnAddressToHand|APairingLine|AClientToHandOver|null $given = null;

    /** @var list<string> */
    private array $carried = [];

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

    /** What it was last handed, or nothing where it never was. */
    public function given(): AnAddressToHand|APairingLine|AClientToHandOver|null
    {
        return $this->given;
    }

    /**
     * The text of everything it was handed, in the order it was.
     *
     * @return list<string>
     */
    public function carried(): array
    {
        return $this->carried;
    }

    public function codeFor(AnAddressToHand|APairingLine|AClientToHandOver $handed): AScannableCode
    {
        $this->given = $handed;
        $this->carried[] = $handed->carried();

        return $this->drawn;
    }
}
