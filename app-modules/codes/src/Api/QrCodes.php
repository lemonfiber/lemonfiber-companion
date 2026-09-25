<?php

declare(strict_types=1);

namespace Modules\Codes\Api;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Exception\WriterException;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Encoding;

/**
 * {@see Encoding}, answered by drawing a QR code of the address.
 *
 * The encoder is handed the address's own text and nothing else — not its
 * caution, and nothing this application wrote — so what another phone reads
 * off the screen is exactly what the stack sent. Error correction is the
 * medium level, which is what a code read off a phone's screen by another
 * phone's camera is usually drawn at.
 *
 * Text the encoder cannot fit in a code is no code at all, which the screen
 * says in words; the address is still there to hand over as text.
 */
final readonly class QrCodes implements Encoding
{
    public function codeFor(AnAddressToHand $address): AScannableCode
    {
        try {
            $matrix = Encoder::encode($address->url(), ErrorCorrectionLevel::M())->getMatrix();
        } catch (WriterException) {
            return AScannableCode::none();
        }

        $rows = [];

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            $row = '';

            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                $row .= $matrix->get($x, $y) === 1 ? '1' : '0';
            }

            $rows[] = $row;
        }

        return AScannableCode::drawn(...$rows);
    }
}
