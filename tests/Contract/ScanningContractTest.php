<?php

declare(strict_types=1);

use Lemonfiber\Native\Scanning as TheCamera;
use Modules\Device\Api\PlatformScanner;
use Modules\Kernel\Api\Permission;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Catalogue;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\ACameraOnAHandset;

// The Scanning contract, run against the adapter and against the fake.
//
// `G2`'s shape, and this port needs it more than most. Every test of the
// scanned road hands its screen an `ACameraInMemory` and never opens a camera,
// so a fake that answered more simply than the platform would make the whole of
// *a camera that was refused is a scanner that refuses* green against a scanner
// that never refuses — which is the one branch nobody can exercise by hand
// without a phone and a denied permission.
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
    $because = $why ?? WhyNothingWasScanned::TheOperatorClosedIt;

    return [
        'the fake' => fn(): Scanning => is_string($payload)
            ? ACameraInMemory::reading($payload)
            : ACameraInMemory::answering($because),
        'the adapter' => fn(): Scanning => overAHandsetsCamera(is_string($payload)
            ? ACameraOnAHandset::reading($payload)
            : whatACameraAnswering($because)),
    ];
}

/**
 * The adapter, over a bridge scripted to answer the way a handset would.
 *
 * `FakeBridge` is `nativephp/mobile`'s own seam: it intercepts
 * `nativephp_call()` in-process, so the adapter runs the real call — the
 * function name from the manifest, the JSON out, the JSON back — rather than
 * something built to resemble it.
 *
 * Named for this file: the root suites share one namespace (`G10`).
 */
function overAHandsetsCamera(ACameraOnAHandset $camera): PlatformScanner
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Scanning.Read', $camera->read(...));

    return new PlatformScanner(new TheCamera(), Catalogue::words());
}

/**
 * A handset's camera in whichever of the four states a test named.
 *
 * The two refusals come back from the bridge under one word and differ only in
 * whether asking again could change the answer, which is exactly the thing the
 * adapter is being asked to get right.
 */
function whatACameraAnswering(WhyNothingWasScanned $why): ACameraOnAHandset
{
    return match ($why) {
        WhyNothingWasScanned::TheOperatorClosedIt => ACameraOnAHandset::closed(),
        WhyNothingWasScanned::TheCameraWasDeclined => ACameraOnAHandset::declined(),
        WhyNothingWasScanned::TheCameraIsNotPermitted => ACameraOnAHandset::refusedForGood(),
        WhyNothingWasScanned::ThereIsNoCamera => ACameraOnAHandset::absent(),
    };
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
    // The distinction the whole refusal enum exists for. A scanner somebody
    // dismissed is answered by offering another go; a camera turned off in
    // settings by naming Settings; a camera declined a moment ago by simply
    // asking again. One sentence for all of them is the sentence that is wrong
    // for whichever the operator is actually in.
    //
    // Over every case rather than a list written here, so a reason added to the
    // vocabulary is carried by both implementations or fails.
    foreach (WhyNothingWasScanned::cases() as $why) {
        foreach (everyCamera(null, $why) as $which => $make) {
            expect(whatCameBack($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('N4-R2 — captions the camera with this application\'s own sentence', function (): void {
    // An adapter-only property, so it is asserted against the adapter alone:
    // the fake has no camera to caption and nothing to say over it.
    //
    // The sentence matters more here than anywhere else it appears. The
    // platform paints it over the preview, and on the first scan the same call
    // raises the permission dialog — so it is the last thing the operator reads
    // before the system takes over. A scanner captioned in English on a Dutch
    // device, or captioned with a key, is what the catalogue exists to prevent.
    $camera = ACameraOnAHandset::closed();

    overAHandsetsCamera($camera)->forAPairingCode(static function (): void {});

    expect($camera->whatItSaid())->toBe([Catalogue::words()->for(Permission::Camera->reason())]);
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
