<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformScanner;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\AScannerThatWasPointedAt;

// The Scanning contract, run against the adapter and against the fake.
//
// `G2`'s shape, and this port needs it more than most. Every test of the
// scanned road hands its screen an `ACameraInMemory` and never opens a camera,
// so a fake that answered more simply than the platform would make the whole of
// `N4-R3` green against a scanner that never refuses — which is the one branch
// nobody can exercise by hand without a phone and a denied permission.
//
// The asymmetry worth naming: on a handset the answer arrives from the runloop
// *after* `forAPairingCode()` has returned, and here both arms answer inside
// it. Every assertion below is written in terms of what the callback was given
// rather than when, so the difference cannot hide a disagreement — and the one
// thing it could hide, that an implementation answers twice or not at all, is
// counted.

/**
 * Every implementation of the port, each told what the camera is about to see.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * @return array<string, Closure(): Scanning>
 */
function everyCamera(?string $payload, ?WhyNothingWasScanned $why = null): array
{
    return [
        'the fake' => fn(): Scanning => is_string($payload)
            ? ACameraInMemory::reading($payload)
            : ACameraInMemory::answering($why ?? WhyNothingWasScanned::TheOperatorClosedIt),
        'the adapter' => fn(): Scanning => new PlatformScanner(
            AScannerThatWasPointedAt::it($payload, $why)->opened(...),
            Catalogue::words(),
        ),
    ];
}

/**
 * One word carried out of an `either()` arm.
 *
 * `WhatTheCameraSaw::either()` answers with an object, so that a caller cannot
 * pull a payload out without saying what happens the other way. A test still
 * wants to compare a string, and this is the smallest honest way across.
 *
 * Named for this file rather than something general: the root suites share one
 * namespace, and two classes of a name are a fatal the moment both load (`G10`).
 */
final readonly class WhatTheScanCameBackWith
{
    public function __construct(public string $said) {}
}

/** What the camera answered, as a word, whichever arm it took. */
function whatCameBack(Scanning $camera): string
{
    $said = 'nothing was answered at all';

    $camera->forAPairingCode(static function (WhatTheCameraSaw $saw) use (&$said): void {
        $said = $saw->either(
            read: static fn(string $payload): WhatTheScanCameBackWith => new WhatTheScanCameBackWith($payload),
            nothing: static fn(WhyNothingWasScanned $why): WhatTheScanCameBackWith => new WhatTheScanCameBackWith($why->value),
        )->said;
    });

    return $said;
}

it('hands back exactly what the camera read', function (): void {
    foreach (everyCamera('{"address":"https://192.168.1.42"}') as $which => $make) {
        expect(whatCameBack($make()))->toBe('{"address":"https://192.168.1.42"}', $which);
    }
});

it('does not decide whether what it read is pairing material', function (): void {
    // The port carries characters. Whether they parse is `WhatTheCodeSaysSoFar`'s
    // question, and it is asked once for both roads — a scanner that refused
    // here would be a second parser, and the second parser is the one that stops
    // being tested.
    foreach (everyCamera('not a code at all') as $which => $make) {
        expect(whatCameBack($make()))->toBe('not a code at all', $which);
    }
});

it('N4-R3 — a refused camera is told apart from one somebody closed', function (): void {
    // The distinction the whole refusal enum exists for. A declined camera is
    // answered by offering the typed road and by naming Settings; a scanner
    // somebody dismissed is answered by offering another go. One sentence for
    // both is the sentence that is wrong for one of them.
    foreach ([
        WhyNothingWasScanned::TheOperatorClosedIt,
        WhyNothingWasScanned::TheCameraIsNotPermitted,
        WhyNothingWasScanned::ThereIsNoCamera,
    ] as $why) {
        foreach (everyCamera(null, $why) as $which => $make) {
            expect(whatCameBack($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('answers once per attempt, and answers at all', function (): void {
    // The one thing the fake's synchrony could hide. An implementation that
    // answered twice would run a screen's pairing twice; one that answered
    // never would leave an operator in front of a camera that closed and a
    // screen that says nothing.
    foreach (everyCamera('anything') as $which => $make) {
        $answers = 0;

        // What was seen is not read, deliberately: this counts the calls, and
        // the arms above are where what they carried is checked.
        $make()->forAPairingCode(static function () use (&$answers): void {
            $answers++;
        });

        expect($answers)->toBe(1, $which);
    }
});
