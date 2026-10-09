<?php

declare(strict_types=1);

use Lemonfiber\Native\Player\Player;
use Modules\Device\Api\PlatformPlayer;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\WhatOpeningCameTo;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\WhyPlayingDidNotStart;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\TheWordCarriedOut;

// The Playing contract, run against the adapter and against the fake.
//
// The adapter arm runs over a bridge scripted into `FakeBridge`, so it goes
// through the function names from the manifest and the decoding of the answer
// rather than through something built to resemble them. There is no player
// behind a PHP process on a laptop.

beforeEach(function (): void {
    FakeBridge::disable();
});

/**
 * The player, over a bridge answering one call with this. Named for this file.
 *
 * @param array<string, mixed> $answer
 */
function aPlayerOverABridgeThatSays(string $call, array $answer): PlatformPlayer
{
    FakeBridge::enable()->respondTo($call, $answer);

    return new PlatformPlayer(new Player());
}

/** One title as the core states it, under a grant. */
function aTitleTheCoreStated(): ATitleToPlay
{
    return ATitleToPlay::of(
        Location::of('https://door.example:8443/videos/a1/master.m3u8'),
        Fingerprint::of(str_repeat('ab', 32)),
        AGrant::of(str_repeat('0c', 16), Instant::atEpochSeconds(2_000_000_000)),
        HowFarIn::at(61),
        'Alien',
    );
}

/** What putting the player on screen came to, as a word. */
function whatOpeningWas(WhatOpeningCameTo $came): string
{
    return $came->either(
        opened: static fn(): TheWordCarriedOut => new TheWordCarriedOut('opened'),
        refused: static fn(WhyPlayingDidNotStart $why): TheWordCarriedOut => new TheWordCarriedOut($why->name),
    )->said;
}

/** Where the player stands, as a line. */
function whereItStood(WherePlayingStands $stands): string
{
    return sprintf('%s at %d', $stands->is()->name, $stands->howFarIn()->seconds());
}

it('puts the player on screen with exactly what the core stated, and no language', function (): void {
    $adapter = aPlayerOverABridgeThatSays('Lemonfiber.Player.Open', ['outcome' => 'showing']);

    foreach (['the fake' => APlayerOnAHandset::working(), 'the adapter' => $adapter] as $which => $playing) {
        expect(whatOpeningWas($playing->open(aTitleTheCoreStated())))->toBe('opened', $which);
    }

    expect(array_column(FakeBridge::enable()->callsTo('Lemonfiber.Player.Open'), 'params'))->toBe([[
        'location' => 'https://door.example:8443/videos/a1/master.m3u8',
        'fingerprint' => str_repeat('ab', 32),
        'grant' => str_repeat('0c', 16),
        'start_at' => 61,
        'title' => 'Alien',
        'audio' => '',
        'subtitle' => '',
    ]]);
});

it('tells a device with no player from one that would not play what the core stated', function (string $because, WhyPlayingDidNotStart $why): void {
    $adapter = aPlayerOverABridgeThatSays('Lemonfiber.Player.Open', ['outcome' => 'refused', 'because' => $because]);

    foreach (['the fake' => APlayerOnAHandset::refusing($why), 'the adapter' => $adapter] as $which => $playing) {
        expect(whatOpeningWas($playing->open(aTitleTheCoreStated())))->toBe($why->name, $which);
    }
})->with([
    'no player' => ['no_player', WhyPlayingDidNotStart::ThereIsNoPlayerHere],
    'an address off the door' => ['not_at_a_door', WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed],
    'nothing to pin' => ['unpinned', WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed],
    'no grant' => ['no_grant', WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed],
    'a refusal' => ['refused', WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed],
]);

it('says where the player stands, in whole seconds', function (string $stands, string $why, PlaybackIs $is): void {
    $adapter = aPlayerOverABridgeThatSays('Lemonfiber.Player.State', [
        'outcome' => 'said', 'stands' => $stands, 'position' => 61.9, 'duration' => 5400,
        'audio' => [], 'subtitles' => [], 'chosen_audio' => '', 'chosen_subtitle' => '', 'why' => $why,
    ]);
    $fake = APlayerOnAHandset::working()->standing(WherePlayingStands::of($is, HowFarIn::at(61)));

    foreach (['the fake' => $fake, 'the adapter' => $adapter] as $which => $playing) {
        expect(whereItStood($playing->whereItStands()))->toBe(sprintf('%s at 61', $is->name), $which);
    }
})->with([
    'opening' => ['opening', '', PlaybackIs::Opening],
    'playing' => ['playing', '', PlaybackIs::Playing],
    'paused' => ['paused', '', PlaybackIs::Paused],
    'stalled' => ['stalled', '', PlaybackIs::Stalled],
    'ended' => ['ended', '', PlaybackIs::Ended],
    'closed' => ['closed', '', PlaybackIs::Closed],
    'out of reach' => ['stopped', 'unreachable', PlaybackIs::StoppedOutOfReach],
    'stopped for no reason it names' => ['stopped', '', PlaybackIs::StoppedOutOfReach],
    'another certificate' => ['stopped', 'pin_mismatch', PlaybackIs::StoppedByThePin],
    'a format it cannot play' => ['stopped', 'unsupported_format', PlaybackIs::StoppedOnTheFormat],
    'refused at the door' => ['stopped', 'refused', PlaybackIs::StoppedAtTheDoor],
]);

it('stands closed at the start where nothing answers', function (): void {
    FakeBridge::enable();

    foreach (['the fake' => APlayerOnAHandset::working(), 'the adapter' => new PlatformPlayer(new Player())] as $which => $playing) {
        expect(whereItStood($playing->whereItStands()))->toBe('Closed at 0', $which);
    }
});

it('takes the player off screen and says it is closed', function (): void {
    $adapter = aPlayerOverABridgeThatSays('Lemonfiber.Player.Close', ['outcome' => 'done']);
    $fake = APlayerOnAHandset::working();

    foreach (['the fake' => $fake, 'the adapter' => $adapter] as $which => $playing) {
        expect(whereItStood($playing->close()))->toBe('Closed at 0', $which);
    }

    expect($fake->timesClosed())->toBe(1);
    FakeBridge::enable()->assertCalled('Lemonfiber.Player.Close');
});
