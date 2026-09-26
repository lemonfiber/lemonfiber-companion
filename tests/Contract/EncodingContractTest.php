<?php

declare(strict_types=1);

use Modules\Codes\Api\QrCodes;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\Encoding;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;

// The Encoding contract, run against the adapter and against the fake.
//
// `G2`'s shape. What both owe a screen is a square of dark and light it can
// draw for an address, and none at all for text that cannot be drawn — never
// an empty square somebody would try to scan.

/** Whether a code is a square with dark and light in it. */
function isASquareWithSomethingInIt(AScannableCode $code): bool
{
    $rows = iterator_to_array($code, preserve_keys: true);
    $squares = implode('', $rows);

    return count($rows) > 0
        && array_all($rows, static fn(string $row): bool => mb_strlen($row) === count($rows))
        && str_contains($squares, '1')
        && str_contains($squares, '0');
}

it('draws an address as a square of dark and light', function (): void {
    $address = AnAddressToHand::at('http://loft.local:8096', 'The number can change');

    foreach (['the fake' => ACodeOfWhatItWasGiven::working(), 'the adapter' => new QrCodes()] as $which => $encoding) {
        expect(isASquareWithSomethingInIt($encoding->codeFor($address)))->toBeTrue($which);
    }
});

it('draws nothing for text that cannot be drawn, rather than an empty square', function (): void {
    $tooLong = AnAddressToHand::at(sprintf('http://loft.local/%s', str_repeat('a', 8000)), '');

    foreach (['the fake' => ACodeOfWhatItWasGiven::drawingNothing(), 'the adapter' => new QrCodes()] as $which => $encoding) {
        expect($encoding->codeFor($tooLong))->toHaveCount(0, $which);
    }
});

it('QrCodes and the fake answer the same port', function (): void {
    expect(QrCodes::class)->toImplement(Encoding::class)
        ->and(ACodeOfWhatItWasGiven::class)->toImplement(Encoding::class);
});
