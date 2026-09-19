<?php

declare(strict_types=1);

use Tests\Support\Tree;

// No player here holds a second copy of what the core already decides.
//
// **This rule outlived the row it was written for.** That row said the app
// plays no media and hands off to a household client, and it has been
// withdrawn: the app plays, because sending somebody to a second application
// they must also install is a household product stopping short of the thing
// the household wanted. What replaced it is narrower — not *no player*, but a
// player holding no second copy of a library, an age limit or an entitlement,
// and none of the asking logic. The cases below name which rows those are.
//
// What it reads for is still every media player, which is **stronger than
// either of those asks** and is held deliberately, for as long as no player can
// be built. Nothing on the wire says where to play a holding — `holdings[]`
// carries an identifier and no location — so a player added today could only
// work by composing an address out of a stack's address and an id, which is
// precisely the second copy of the library that survived. The over-enforcement
// and the gap have the same lifetime: when the contract carries a location,
// this is re-aimed at what the surviving rows actually forbid, and the
// requirements register beside it says so.
//
// A requirement kept by *not* doing something, which is the kind that erodes.
// Nobody decides to turn the companion into a player. What happens is that a
// member taps a title, there is nowhere to send them yet, and playing it right
// there is four lines on each platform — `ExoPlayer.Builder(context).build()`,
// `AVPlayer(url:)` — and looks like the app finally doing something useful.
//
// The reason it is refused is not tidiness. A player is a second surface for
// everything the household client already solves: transcoding decisions,
// resume points, subtitles, parental limits, what counts as watched. Two of
// those disagreeing is a member told they may watch something the client then
// refuses, or a resume point that moves backwards. The member's half is the same
// argument about limits, and this is it about playback.
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
        . 'A player may exist here one day and cannot yet: nothing on the wire says '
        . 'where to play a holding, so one added now could only work by composing an '
        . "address, which is the second copy of the library N3-R14 refuses.\n"
        . 'Until the contract carries a location, hand off to the client that already '
        . 'knows the transcoding, the resume points, the subtitles and what a member is '
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
