<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_key_exists;

use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Encoding;

/**
 * Every code a screen hands over, drawn once before the answer is presented, by the text each carries.
 *
 * A screen draws them because drawing is a port's work, and hands them to its
 * presenter, which asks nothing of anybody; one with several addresses to hand
 * over draws them all here rather than passing each through on its own.
 */
final readonly class TheCodesDrawn
{
    /** @param array<string, AScannableCode> $drawn */
    private function __construct(private array $drawn) {}

    /** The codes for what was handed over; text there is none of draws no code. */
    public static function of(Encoding $encoding, AnAddressToHand|AClientToHandOver ...$handed): self
    {
        $drawn = [];

        foreach ($handed as $one) {
            if ($one->carried() !== '') {
                $drawn[$one->carried()] = $encoding->codeFor($one);
            }
        }

        return new self($drawn);
    }

    /** The code drawn for what was handed over, or none where it was not drawn. */
    public function for(AnAddressToHand|AClientToHandOver $handed): AScannableCode
    {
        return array_key_exists($handed->carried(), $this->drawn) ? $this->drawn[$handed->carried()] : AScannableCode::none();
    }
}
