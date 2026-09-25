<?php

declare(strict_types=1);

use Tests\Support\Tree;

// No player is built here, and this refuses one.
//
// The player the app is meant to have renders what the core says a member may
// watch and holds no second copy of it: no library, no age limit, no
// entitlement, and none of the asking. The cases below name those rows.
//
// What this reads for is every media player, which is **stronger than those
// rows ask**, and it is the only form of them that can be enforced while the
// contract names nothing to play. `holdings[]` carries an identifier, a medium,
// a title and a year: no location a holding can be streamed from, and no
// authorisation for the member to stream it. A player added now could only work
// by composing an address out of a stack's address and an id, and by presenting
// a credential this app does not hold for that member — the second copy of the
// library and of the entitlement those rows refuse. The register of what the
// contract does not carry holds that gap, and its row names this rule as the
// one to replace when the gap closes.
//
// A requirement kept by *not* doing something, which is the kind that erodes.
// Nobody decides to build the player early. What happens is that a member taps
// a title, and playing it right there is four lines on each platform —
// `ExoPlayer.Builder(context).build()`, `AVPlayer(url:)` — pointed at an
// address somebody assembled, which looks like the app finally doing something
// useful and is the app deciding which media server the household runs.
//
// Read over the platform sources rather than over the PHP, because that is
// where it enters: playing media is a platform capability and the PHP side can
// only ask for it. A rule reading `app-modules` would be looking where the
// thing it forbids cannot happen.

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
 * Which of the players a piece of source reaches for.
 *
 * @return list<string>
 */
function playersIn(string $source): array
{
    return array_values(array_filter(
        PLAYS_MEDIA,
        static fn(string $player): bool => str_contains($source, $player),
    ));
}

it('N3-R14 — no platform source reaches for a media player', function (): void {
    // Assert the reading before what it says: a rule whose subjects are
    // discovered has a state in which it examines nothing, and that state looks
    // exactly like every subject passing.
    $sources = platformSources();

    expect($sources)->not->toBe([], 'no platform source was found, so this rule proved nothing');

    $found = [];

    foreach ($sources as $path => $source) {
        foreach (playersIn($source) as $player) {
            $found[] = sprintf('%s reaches for %s', $path, $player);
        }
    }

    sort($found);

    expect($found)->toBe([], sprintf(
        "These play media on the device:\n  %s\n\n"
        . 'No player can be built: nothing on the wire says where a holding is '
        . 'streamed from or authorises the member to stream it, so one added now could '
        . 'only work by composing an address and presenting a credential this app does '
        . "not hold for them, which is the second copy of the library N3-R14 refuses.\n"
        . 'The gap is the stream_from row in WhatTheContractDoesNotCarryTest; until it '
        . 'closes, a member watches in the client that already knows what they are '
        . 'allowed to watch (N3-R14, N3-R16, N3-R11).',
        implode("\n  ", $found),
    ));
});

it('N3-R14 — each player is one this rule would recognise', function (): void {
    // The floor against the matcher rather than against the tree. Planting a player
    // in `bridge/resources` would leave a real source file wrong for the length
    // of a run, and this repository has been bitten by a killed run leaving its
    // fixtures behind.
    //
    // A benign source is checked too, because a matcher that answered yes to
    // everything would pass the loop above it and prove nothing.
    foreach (PLAYS_MEDIA as $player) {
        expect(playersIn(sprintf('val it = %s(context)', $player)))->toContain($player);
    }

    expect(playersIn('class CaptureRule { fun protect() {} }'))->toBe([]);
});
