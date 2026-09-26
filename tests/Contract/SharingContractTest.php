<?php

declare(strict_types=1);

use Lemonfiber\Native\Handover;
use Modules\Device\Api\PlatformShare;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\AnInvitationToPassOn;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\AShareSheetThatWasOffered;

// The Sharing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and the reason this one matters: a diagnostic report is
// assembled for the operator to send and must not be transmitted by
// the app. Every screen test will hand its subject an
// `AShareSheetThatWasOffered` and never open a share sheet, so a fake easier to
// satisfy than the platform would enforce that against something that always
// says yes.
//
// The adapter arm runs over a bridge scripted into `FakeBridge`, which
// intercepts `nativephp_call()` in-process — so it goes through the function
// name from the manifest, the JSON out and the decoding of the answer rather
// than through something built to resemble it. There is no share sheet behind a
// PHP process on a laptop, and without that seam the adapter is a file nothing
// executes.

/**
 * The adapter, over a bridge scripted to answer one way.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of the same name are a fatal the moment both load (`G10`).
 *
 * @param array<string, string> $answer
 */
function overASheetThatSays(array $answer): PlatformShare
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Handover.Offer', $answer);

    return new PlatformShare(new Handover());
}

/** A report to hand over, with nothing in it that could not be. */
function aReportToShare(): Assembled
{
    return Assembled::as('lemonfiber-diagnostics.txt', "lemonfiber companion, state shape 1\napi version 1");
}

/** What a sheet answered, as a word, whichever arm it took. */
function howTheSharingWent(Handed $handed): string
{
    return $handed->either(
        over: static fn(): WhatTheSheetDid => new WhatTheSheetDid('over'),
        refused: static fn(WhyNothingWasShared $why): WhatTheSheetDid => new WhatTheSheetDid($why->value),
    )->said;
}

/**
 * One word carried out of an `either()` arm.
 *
 * Named for this file rather than something general: the root suites share one
 * namespace, and two classes of a name are a fatal the moment both load (`G10`).
 */
final readonly class WhatTheSheetDid
{
    public function __construct(public string $said) {}
}

it('N4-R13 — hands the report over and says it did', function (): void {
    $adapter = overASheetThatSays(['outcome' => 'offered']);

    foreach (['the fake' => AShareSheetThatWasOffered::working(), 'the adapter' => $adapter] as $which => $sharing) {
        expect(howTheSharingWent($sharing->hand(aReportToShare())))->toBe('over', $which);
    }

    // Only the adapter can be asked this: the sheet is offered the report's own
    // name and the report's own text, and nothing else — no path, because
    // nothing was written anywhere.
    FakeBridge::enable()->assertCalled(
        'Lemonfiber.Handover.Offer',
        static fn(array $sent): bool => $sent === [
            'title' => 'lemonfiber-diagnostics.txt',
            'text' => aReportToShare()->text(),
        ],
    );
});

it('N4-R13 — a report that was never assembled refuses rather than crashing', function (): void {
    // Told apart from a platform that would not offer a sheet, because the
    // remedies differ: this one is answered by asking for the report again, and
    // the other by trying the sheet again.
    $adapter = overASheetThatSays(['outcome' => 'refused', 'because' => 'nothing_to_hand_over']);

    foreach ([
        'the fake' => AShareSheetThatWasOffered::refusing(WhyNothingWasShared::NothingToHandOver),
        'the adapter' => $adapter,
    ] as $which => $sharing) {
        expect(howTheSharingWent($sharing->hand(aReportToShare())))
            ->toBe(WhyNothingWasShared::NothingToHandOver->value, $which);
    }
});

it('N4-R13 — a platform that would not show a sheet says so', function (): void {
    $adapter = overASheetThatSays(['outcome' => 'refused', 'because' => 'the_platform_would_not']);

    foreach ([
        'the fake' => AShareSheetThatWasOffered::refusing(WhyNothingWasShared::TheDeviceWouldNotOffer),
        'the adapter' => $adapter,
    ] as $which => $sharing) {
        expect(howTheSharingWent($sharing->hand(aReportToShare())))
            ->toBe(WhyNothingWasShared::TheDeviceWouldNotOffer->value, $which);
    }
});

it('N1-R10 — tells a report that is not there from a platform that would not offer', function (): void {
    // Two refusals, two sentences, two different remedies. One sentence for
    // both is the sentence that is unhelpful for whichever they are in.
    $said = [];

    foreach (WhyNothingWasShared::cases() as $why) {
        $sharing = AShareSheetThatWasOffered::refusing($why);

        expect(howTheSharingWent($sharing->hand(aReportToShare())))->toBe($why->value, $why->value)
            ->and(__($why->saidOnTheScreen()))->not->toBe($why->saidOnTheScreen(), $why->value)
            ->and(__($why->remedy()))->not->toBe($why->remedy(), $why->value);

        $said[] = $why->saidOnTheScreen();
    }

    expect($said)->toHaveCount(count(array_unique($said)));
});

it('passes an invitation on under its name, with the address and its caution, and says it did', function (): void {
    $invitation = AnInvitationToPassOn::of(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://192.168.1.42:8096', 'The number can change'), 72),
        'You are invited into the household',
    );
    $adapter = overASheetThatSays(['outcome' => 'offered']);
    $fake = AShareSheetThatWasOffered::working();

    foreach (['the fake' => $fake, 'the adapter' => $adapter] as $which => $sharing) {
        expect(howTheSharingWent($sharing->passOn($invitation)))->toBe('over', $which);
    }

    expect($fake->passed())->toBe($invitation);

    FakeBridge::enable()->assertCalled(
        'Lemonfiber.Handover.Offer',
        static fn(array $sent): bool => $sent === [
            'title' => 'anna',
            'text' => "You are invited into the household\n\nhttp://192.168.1.42:8096\n\nThe number can change",
        ],
    );
});

it('says so where the platform would not pass an invitation on', function (): void {
    $invitation = AnInvitationToPassOn::of(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        'You are invited into the household',
    );
    $adapter = overASheetThatSays(['outcome' => 'refused', 'because' => 'the_platform_would_not']);

    foreach ([
        'the fake' => AShareSheetThatWasOffered::refusing(WhyNothingWasShared::TheDeviceWouldNotOffer),
        'the adapter' => $adapter,
    ] as $which => $sharing) {
        expect(howTheSharingWent($sharing->passOn($invitation)))
            ->toBe(WhyNothingWasShared::TheDeviceWouldNotOffer->value, $which);
    }
});

it('PlatformShare answers the same port', function (): void {
    expect(PlatformShare::class)->toImplement(Sharing::class);
});
