<?php

declare(strict_types=1);

use Lemonfiber\Native\Handover;
use Lemonfiber\Native\Offered;
use Lemonfiber\Native\WhyNothingWasHandedOver;
use Native\Mobile\Testing\FakeBridge;

// The handover capability's PHP face, driven through the real bridge call.
//
// `FakeBridge` intercepts `nativephp_call()` in-process, so every assertion
// here goes through the function name, the JSON out and the decoding of the
// answer rather than through something built to resemble it.
//
// The bridge name is written out as a literal, deliberately. `Handover` reaches
// it through `Call`, so a test spelling it `Call::Offer->value` would agree with
// a wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json` separately.

beforeEach(function (): void {
    FakeBridge::disable();
});

/** Why the sheet was refused, or nothing because it was not. */
function whyTheSheetWasRefused(Offered $offered): ?WhyNothingWasHandedOver
{
    $answered = $offered->either(
        offered: static fn(): ArrayObject => new ArrayObject([null]),
        refused: static fn(WhyNothingWasHandedOver $why): ArrayObject => new ArrayObject([$why]),
    );

    $why = $answered[0];

    return $why instanceof WhyNothingWasHandedOver ? $why : null;
}

it('sends the title and the report, and nothing else', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', ['outcome' => 'offered']);

    expect(whyTheSheetWasRefused(new Handover()->offer('a-report.txt', 'what is wrong')))->toBeNull();

    $bridge->assertCalled(
        'Lemonfiber.Handover.Offer',
        static fn(array $sent): bool => $sent === ['title' => 'a-report.txt', 'text' => 'what is wrong'],
    );
});

it('reads each refusal the sheet can answer with', function (): void {
    foreach (WhyNothingWasHandedOver::cases() as $why) {
        FakeBridge::disable();
        FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', [
            'outcome' => 'refused',
            'because' => $why->value,
        ]);

        expect(whyTheSheetWasRefused(new Handover()->offer('a', 'b')))->toBe($why);
    }
});

it('reads a refusal it cannot explain as the platform having refused', function (): void {
    // The recoverable of the two. It says try again, where the other says the
    // report was never assembled and trying again will do nothing.
    FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', [
        'outcome' => 'refused',
        'because' => 'some_word_from_a_newer_shim',
    ]);

    expect(whyTheSheetWasRefused(new Handover()->offer('a', 'b')))
        ->toBe(WhyNothingWasHandedOver::ThePlatformWouldNot);
});

it('reads no answer at all as the platform having refused', function (): void {
    // Every machine that is not a handset. There is no sheet, so nothing was
    // put in front of anybody, and a stand-in claiming otherwise would make a
    // test about offering a report pass where there is nothing to offer it to.
    FakeBridge::enable();

    expect(whyTheSheetWasRefused(new Handover()->offer('a', 'b')))
        ->toBe(WhyNothingWasHandedOver::ThePlatformWouldNot);
});

it('reads an answer that is not an envelope as the platform having refused', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', ['nothing_useful' => true]);

    expect(whyTheSheetWasRefused(new Handover()->offer('a', 'b')))
        ->toBe(WhyNothingWasHandedOver::ThePlatformWouldNot);
});

it('does not read an outcome that is not a word as the sheet having opened', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', ['outcome' => 42]);

    expect(whyTheSheetWasRefused(new Handover()->offer('a', 'b')))
        ->toBe(WhyNothingWasHandedOver::ThePlatformWouldNot);
});
