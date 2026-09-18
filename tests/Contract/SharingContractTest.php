<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformShare;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Handed;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\WhyNothingWasShared;
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
// The adapter is driven through a closure standing in for `Share::file()`,
// which is what `G1` asks for: there is no share sheet behind a PHP process on
// a laptop, and without the stand-in the adapter is a file nothing executes —
// so the three things it decides (that a report is written where the platform
// can read it, that a directory it cannot write is a refusal rather than a
// crash, and that the sheet is asked for exactly once) would go unchecked until
// somebody held a phone.

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
    $written = [];
    $adapter = new PlatformShare(
        static function (string $title, string $text, string $path) use (&$written): void {
            $written[] = [$title, $text, $path];
        },
        sys_get_temp_dir(),
    );

    foreach (['the fake' => AShareSheetThatWasOffered::working(), 'the adapter' => $adapter] as $which => $sharing) {
        expect(howTheSharingWent($sharing->hand(aReportToShare())))->toBe('over', $which);
    }

    // Only the adapter can be asked this: the sheet is asked for once, with the
    // report's own name and a path a chosen app can read.
    expect($written)->toHaveCount(1)
        ->and($written[0][0])->toBe('lemonfiber-diagnostics.txt')
        ->and($written[0][2])->toEndWith('lemonfiber-diagnostics.txt');

    expect(file_get_contents($written[0][2]))->toBe(aReportToShare()->text());

    unlink($written[0][2]);
});

it('N4-R13 — a device with nowhere to write it refuses rather than crashing', function (): void {
    // The commonest failure on a phone holding this product's media, and the
    // one an operator can actually do something about — which is why it is told
    // apart from a platform that would not offer a sheet at all.
    $reached = 0;
    $adapter = new PlatformShare(
        static function () use (&$reached): void {
            $reached++;
        },
        '/a/directory/that/is/not/there',
    );

    foreach ([
        'the fake' => AShareSheetThatWasOffered::refusing(WhyNothingWasShared::NowhereToWriteIt),
        'the adapter' => $adapter,
    ] as $which => $sharing) {
        expect(howTheSharingWent($sharing->hand(aReportToShare())))
            ->toBe(WhyNothingWasShared::NowhereToWriteIt->value, $which);
    }

    // Counted rather than raised from inside the closure, which the analyser
    // forbids — and the count is the better assertion anyway: the sheet must
    // not be reached at all where nothing was written, and a raise would only
    // have said it was reached once.
    expect($reached)->toBe(0);
});

it('N1-R10 — tells a full disk from a platform that would not offer', function (): void {
    // Two refusals, two sentences, and only one of them is something the
    // operator can fix. One sentence for both is the sentence that is unhelpful
    // for whichever they are in.
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

it('PlatformShare answers the same port', function (): void {
    expect(PlatformShare::class)->toImplement(Sharing::class);
});
