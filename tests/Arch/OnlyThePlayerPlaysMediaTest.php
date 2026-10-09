<?php

declare(strict_types=1);

use Tests\Support\Tree;

// The player is built in one place, plays only what it is handed, and is
// handed nothing until the core says where a title streams from.
//
// The player the app has renders what the core says a member may watch and
// holds no second copy of it: no library, no age limit, no entitlement, and
// none of the asking. It cannot compose an address, because composing one out
// of a stack's address and a holding's id is a second copy of how the library
// works, and it is server-specific besides — the app would be deciding which
// media server the household runs. So the location and the member's grant are
// the core's, and the player plays them and nothing else.
//
// A requirement kept by *not* doing something, which is the kind that erodes.
// Nobody decides to build a second player. What happens is that a screen wants
// a trailer, and playing it right there is four lines on each platform —
// `ExoPlayer.Builder(context).build()`, `AVPlayer(url:)` — pointed at an
// address somebody assembled, past the pin and past the door. These rules are
// what make that a red run rather than a review comment.
//
// Read over the platform sources, because that is where playing enters: the
// PHP side can only ask for it.

/** How each platform is asked to play something. */
const PLAYS_MEDIA = [
    // Android — the modern one and the two it replaced, plus the session a
    // player publishes so the lock screen can control it.
    'ExoPlayer', 'androidx.media3', 'MediaPlayer', 'VideoView', 'MediaSession',
    // iOS — the player, the queue, the view controller that presents one, and
    // the two audio paths that do not go through `AVPlayer` at all.
    'AVPlayer', 'AVQueuePlayer', 'AVPlayerViewController', 'AVAudioPlayer',
    'AVAudioEngine', 'MPMusicPlayerController',
];

/**
 * The player's own files: the one place a media player may be reached for.
 *
 * Each fetches through the door or plays what was fetched through it. The
 * rules it is decided by — the door, the pin, the playlists, the tracks — are
 * the pure files beside them, tested on a laptop and reaching for nothing.
 */
const THE_PLAYER = [
    'bridge/resources/android/DoorDataSource.kt',
    'bridge/resources/android/PlaybackService.kt',
    'bridge/resources/android/PlayerActivity.kt',
    'bridge/resources/ios/DoorLoader.swift',
    'bridge/resources/ios/PlayerScreen.swift',
];

/**
 * How a player is pointed at an address it fetches by itself, past the door.
 *
 * Media3's own sources open a connection the platform trusts rather than the
 * one the pin trusts; `AVPlayer` handed an `https` address fetches it without
 * asking the loader.
 */
const FETCHES_PAST_THE_DOOR = [
    'DefaultHttpDataSource', 'DefaultDataSource', 'OkHttpDataSource', 'CronetDataSource',
    'AVPlayer(url', 'AVPlayerItem(url', 'AVQueuePlayer(url',
];

/**
 * Every platform source this repository ships, against its contents.
 *
 * @return array<string, string> path => contents
 */
function platformSources(): array
{
    $found = [];

    foreach (['.kt', '.swift'] as $suffix) {
        foreach (Tree::filesUnder(Tree::at('bridge/resources'), $suffix) as $path) {
            $contents = file_get_contents($path);

            if (is_string($contents)) {
                $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
            }
        }
    }

    return $found;
}

/**
 * Which of a list of words a piece of source contains.
 *
 * @param list<string> $words
 * @return list<string>
 */
function wordsIn(string $source, array $words): array
{
    return array_values(array_filter($words, static fn(string $word): bool => str_contains($source, $word)));
}

/**
 * The Android services the plugin's manifest declares.
 *
 * A function rather than a line in the test, because `json_decode` with
 * `JSON_THROW_ON_ERROR` throws a checked exception, and one raised inside a
 * Pest closure is one nothing declares.
 *
 * @return list<array{name: string, exported?: bool}>
 */
function theServicesTheBridgeDeclares(): array
{
    /** @var array{android: array{services: list<array{name: string, exported?: bool}>}} $said */
    $said = json_decode(
        (string) file_get_contents(Tree::at('bridge/nativephp.json')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    return $said['android']['services'];
}

it('no platform source but the player reaches for a media player', function (): void {
    // Assert the reading before what it says: a rule whose subjects are
    // discovered has a state in which it examines nothing, and that state looks
    // exactly like every subject passing.
    $sources = platformSources();

    expect($sources)->not->toBe([], 'no platform source was found, so this rule proved nothing');

    $found = [];

    foreach ($sources as $path => $source) {
        foreach (in_array($path, THE_PLAYER, strict: true) ? [] : wordsIn($source, PLAYS_MEDIA) as $player) {
            $found[] = sprintf('%s reaches for %s', $path, $player);
        }
    }

    sort($found);

    expect($found)->toBe([], sprintf(
        "These play media outside the player:\n  %s\n\n"
        . 'The player is the one place media is played, because it is the one place every '
        . 'byte is fetched through the door over the pinned connection, from an address the '
        . 'core stated. A second player is a second way past both (N3-R14).',
        implode("\n  ", $found),
    ));
});

it('the player fetches nothing past the door', function (): void {
    $found = [];

    foreach (THE_PLAYER as $path) {
        $source = (string) file_get_contents(Tree::at($path));

        expect($source)->not->toBe('', sprintf('%s is gone, so this rule proved nothing about it', $path));

        foreach (wordsIn($source, FETCHES_PAST_THE_DOOR) as $past) {
            $found[] = sprintf('%s reaches for %s', $path, $past);
        }
    }

    expect($found)->toBe([], sprintf(
        "These fetch past the door:\n  %s\n\n"
        . 'Every fetch goes through DoorDataSource on Android and DoorLoader on iOS, which '
        . 'admit only the certificate the core stated and only addresses at its door (N3-R14).',
        implode("\n  ", $found),
    ));
});

it('every source the player builds reads through the door', function (): void {
    // The positive half of the rule above: a source factory left to its
    // defaults reaches for the platform's own connection without naming it.
    $android = (string) file_get_contents(Tree::at('bridge/resources/android/PlaybackService.kt'));
    $ios = (string) file_get_contents(Tree::at('bridge/resources/ios/PlayerScreen.swift'));

    expect(substr_count($android, 'DefaultMediaSourceFactory('))
        ->toBeGreaterThan(0)
        ->toBe(substr_count($android, 'DefaultMediaSourceFactory(DoorDataSource.Factory('))
        ->and(substr_count($ios, 'AVURLAsset(url:'))
        ->toBeGreaterThan(0)
        ->toBe(substr_count($ios, 'AVURLAsset(url: handed)'))
        ->and($ios)->toContain('resourceLoader.setDelegate(loader');
});

it('the player fetches the address the door admitted, and nothing parsed a second time', function (): void {
    // The door reads an address twice — by its own grammar and as the platform
    // does — and hands back the platform's reading only where the two agree.
    // Fetching anything else, the text re-parsed or a redirect followed as the
    // session found it, reopens the gap between what was checked and what is
    // fetched.
    $ios = (string) file_get_contents(Tree::at('bridge/resources/ios/DoorLoader.swift'));
    $android = (string) file_get_contents(Tree::at('bridge/resources/android/DoorDataSource.kt'));

    expect($ios)->toContain('guard let url = asked.door.admitted(address)')
        ->and($ios)->toContain('let admitted = asked.door.admitted(next.absoluteString)')
        ->and($ios)->toContain('followed.url = admitted')
        ->and($ios)->not->toContain('URL(string: address)')
        ->and($android)->toContain('val admitted = asked.door.admitted(address) ?: refuse(dataSpec)')
        ->and($android)->toContain('admitted.toURL().openConnection()')
        ->and($android)->not->toContain('URL(address)');
});

it('only this app and the system connect to the player', function (): void {
    // Any app on the phone may ask to connect to a media session. The service
    // is not exported, and every connection is put to the controller rule.
    $service = (string) file_get_contents(Tree::at('bridge/resources/android/PlaybackService.kt'));
    $exported = array_map(
        static fn(array $declared): array => [$declared['name'], $declared['exported'] ?? null],
        theServicesTheBridgeDeclares(),
    );

    expect($exported)->toContain(['app.lemonfiber.native.PlaybackService', false])
        ->and($service)->toContain('.setCallback(OnlyTheAppAndTheSystem())')
        ->and($service)->toContain('session?.takeIf { admits(this, controllerInfo) }')
        ->and($service)->toContain('MediaSession.ConnectionResult.reject()')
        ->and($service)->toContain('ControllerRule.admits(');
});

it('nothing in the app opens the player while the contract says nowhere to stream from', function (): void {
    // The half of this rule that waits on the core. The player plays what it is
    // handed; until a holding carries where it streams from and the member's
    // grant for it, anything handing the player an address composed it, which is
    // the second copy of the library this refuses. The gap is the stream_from row
    // in WhatTheContractDoesNotCarryTest, and the day it closes this test is the
    // one to replace with the household's own player screen.
    $found = [];

    foreach (['app', 'app-modules', 'bootstrap', 'routes'] as $tree) {
        foreach (Tree::filesUnder(Tree::at($tree), '.php') as $path) {
            if (str_contains((string) file_get_contents($path), 'Lemonfiber\\Native\\Player\\')) {
                $found[] = str_replace(sprintf('%s/', Tree::root()), '', $path);
            }
        }
    }

    sort($found);

    expect($found)->toBe([], sprintf(
        "These open the player:\n  %s\n\n"
        . 'Nothing on the wire says where a holding is streamed from or authorises the member '
        . 'to stream it, so whatever these hand the player was composed here (N3-R14, N3-R11).',
        implode("\n  ", $found),
    ));
});

it('each word is one these rules would recognise', function (): void {
    // The floor against the matcher rather than against the tree. Planting a player
    // in `bridge/resources` would leave a real source file wrong for the length
    // of a run, and this repository has been bitten by a killed run leaving its
    // fixtures behind.
    //
    // A benign source is checked too, because a matcher that answered yes to
    // everything would pass the loops above it and prove nothing.
    foreach ([...PLAYS_MEDIA, ...FETCHES_PAST_THE_DOOR] as $word) {
        expect(wordsIn(sprintf('val it = %s(context)', $word), [...PLAYS_MEDIA, ...FETCHES_PAST_THE_DOOR]))
            ->toContain($word);
    }

    expect(wordsIn('class CaptureRule { fun protect() {} }', [...PLAYS_MEDIA, ...FETCHES_PAST_THE_DOOR]))->toBe([]);
});
