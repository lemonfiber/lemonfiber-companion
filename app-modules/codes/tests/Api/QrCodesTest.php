<?php

declare(strict_types=1);

namespace Modules\Codes\Tests\Api;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Codes\Api\QrCodes;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\APairingLine;

use function sprintf;
use function str_repeat;

/**
 * The squares the encoder itself makes of a text, row by row, `1` for dark.
 *
 * @return list<string>
 */
function theSquaresTheEncoderMakes(string $text): array
{
    $matrix = Encoder::encode($text, ErrorCorrectionLevel::M())->getMatrix();
    $rows = [];

    for ($y = 0; $y < $matrix->getHeight(); $y++) {
        $row = '';

        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            $row .= $matrix->get($x, $y) === 1 ? '1' : '0';
        }

        $rows[] = $row;
    }

    return $rows;
}

it('draws exactly the squares the encoder makes of the address, at medium correction, row by row', function (): void {
    $drawn = iterator_to_array(new QrCodes()->codeFor(AnAddressToHand::at('http://loft.local:8096', 'The number can change')), preserve_keys: true);

    expect($drawn)->toBe(theSquaresTheEncoderMakes('http://loft.local:8096'))
        ->and($drawn)->toHaveCount(25);
});

it('draws the address and not its caution', function (): void {
    $withCaution = iterator_to_array(new QrCodes()->codeFor(AnAddressToHand::at('http://192.168.1.42:8096', 'The number can change')), preserve_keys: true);
    $without = iterator_to_array(new QrCodes()->codeFor(AnAddressToHand::at('http://192.168.1.42:8096', '')), preserve_keys: true);

    expect($withCaution)->toBe($without);
});

it('draws the line a phone pairs with, exactly as the stack wrote it', function (): void {
    $line = sprintf(
        '{"address":"https://den.local:8443","fingerprint":"%s","expires":1790813400,"stack":"000102030405060708090a0b0c0d0e0f"}',
        str_repeat('ab', 32),
    );

    expect(iterator_to_array(new QrCodes()->codeFor(APairingLine::asWritten($line)), preserve_keys: true))
        ->toBe(theSquaresTheEncoderMakes($line));
});
