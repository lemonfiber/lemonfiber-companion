<?php

declare(strict_types=1);

use Lemonfiber\Native\Player\WherePlaybackStands;
use Lemonfiber\Native\Player\WhyPlaybackStopped;
use Lemonfiber\Native\Player\WhyThePlayerDidNotOpen;

// Every word the player answers with, and what this side makes of one it does
// not know. Nothing said and a word not known take separate branches for the
// reason `WhatTheBridgeSaidTest` gives, so each reader is driven with both.

it('reads the word the device said', function (): void {
    expect(WhyThePlayerDidNotOpen::orNoPlayerHere('unpinned'))->toBe(WhyThePlayerDidNotOpen::Unpinned)
        ->and(WhyThePlayerDidNotOpen::orNoPlayerHere('no_player'))->toBe(WhyThePlayerDidNotOpen::NoPlayerHere)
        ->and(WherePlaybackStands::orClosed('stalled'))->toBe(WherePlaybackStands::Stalled)
        ->and(WherePlaybackStands::orClosed('closed'))->toBe(WherePlaybackStands::Closed)
        ->and(WhyPlaybackStopped::orNone('unsupported_format'))->toBe(WhyPlaybackStopped::UnsupportedFormat)
        ->and(WhyPlaybackStopped::orNone('unreachable'))->toBe(WhyPlaybackStopped::Unreachable);
});

it('reads a word it does not know as the fallback', function (): void {
    expect(WhyThePlayerDidNotOpen::orNoPlayerHere('later'))->toBe(WhyThePlayerDidNotOpen::NoPlayerHere)
        ->and(WherePlaybackStands::orClosed('rewinding'))->toBe(WherePlaybackStands::Closed)
        ->and(WhyPlaybackStopped::orNone('later'))->toBe(WhyPlaybackStopped::Unreachable);
});

it('reads nothing said as the fallback', function (): void {
    expect(WhyThePlayerDidNotOpen::orNoPlayerHere(null))->toBe(WhyThePlayerDidNotOpen::NoPlayerHere)
        ->and(WherePlaybackStands::orClosed(null))->toBe(WherePlaybackStands::Closed)
        ->and(WhyPlaybackStopped::orNone(null))->toBeNull();
});

it('reads an empty reason as playback not having stopped', function (): void {
    expect(WhyPlaybackStopped::orNone(''))->toBeNull();
});

it('speaks the same words as both native halves', function (): void {
    // The words are the wire; the Kotlin and Swift enums answer these and only
    // these, so a renamed case here is a state nobody on the device sends.
    expect(array_map(static fn(WherePlaybackStands $one): string => $one->value, WherePlaybackStands::cases()))
        ->toBe(['opening', 'playing', 'paused', 'stalled', 'ended', 'stopped', 'closed'])
        ->and(array_map(static fn(WhyPlaybackStopped $one): string => $one->value, WhyPlaybackStopped::cases()))
        ->toBe(['unreachable', 'pin_mismatch', 'unsupported_format', 'refused'])
        ->and(array_map(static fn(WhyThePlayerDidNotOpen $one): string => $one->value, WhyThePlayerDidNotOpen::cases()))
        ->toBe([
            'not_at_a_door',
            'unpinned',
            'no_grant',
            'grant_in_the_address',
            'no_starting_point',
            'refused',
            'no_player',
        ]);
});
