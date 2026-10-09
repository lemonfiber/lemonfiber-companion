<?php

declare(strict_types=1);

use Lemonfiber\Native\Player\OfferedTrack;
use Lemonfiber\Native\Player\Player;
use Lemonfiber\Native\Player\PlayerCommand;
use Lemonfiber\Native\Player\TitleAtTheDoor;
use Lemonfiber\Native\Player\WherePlaybackStands;
use Lemonfiber\Native\Player\WhyPlaybackStopped;
use Lemonfiber\Native\Player\WhyThePlayerDidNotOpen;
use Native\Mobile\Testing\FakeBridge;

// The player's PHP face, driven through the real bridge call.
//
// The bridge names are written out as literals, deliberately: `Player` reaches
// them through `Call`, so a test spelling them `Call::PlayerOpen->value` would
// agree with a wrong enum and prove nothing. `CallTest` holds the enum against
// `nativephp.json`.

beforeEach(function (): void {
    FakeBridge::disable();
});

/** One title as the core would state it, for the player to be handed. */
function aTitleAtTheDoor(): TitleAtTheDoor
{
    return new TitleAtTheDoor(
        location: 'https://door.example:8443/videos/1/master.m3u8',
        fingerprint: str_repeat('ab', 32),
        grant: 'the-members-grant',
        startAt: 61.5,
        title: 'A film',
        audio: 'nl',
        subtitle: 'off',
    );
}

/**
 * What the device was handed by every call to one function, as it received it.
 *
 * @return list<mixed>
 */
function whatThePlayerWasHanded(FakeBridge $bridge, string $function): array
{
    return array_column($bridge->callsTo($function), 'params');
}

it('hands the device every field of the title, the grant among them', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Player.Open', ['outcome' => 'showing']);

    $opened = new Player()->open(aTitleAtTheDoor())->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyThePlayerDidNotOpen $why): ArrayObject => new ArrayObject([$why]),
    );

    expect($opened->getArrayCopy())->toBe(['opened'])
        ->and(whatThePlayerWasHanded($bridge, 'Lemonfiber.Player.Open'))->toBe([[
            'location' => 'https://door.example:8443/videos/1/master.m3u8',
            'fingerprint' => str_repeat('ab', 32),
            'grant' => 'the-members-grant',
            'start_at' => 61.5,
            'title' => 'A film',
            'audio' => 'nl',
            'subtitle' => 'off',
        ]]);
});

it('reads why the device would not open the player', function (): void {
    FakeBridge::enable()->respondTo(
        'Lemonfiber.Player.Open',
        ['outcome' => 'refused', 'because' => 'grant_in_the_address'],
    );

    $why = new Player()->open(aTitleAtTheDoor())->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyThePlayerDidNotOpen $why): ArrayObject => new ArrayObject([$why]),
    );

    expect($why->getArrayCopy())->toBe([WhyThePlayerDidNotOpen::GrantInTheAddress]);
});

it('reads no answer at all as there being no player here', function (): void {
    // Every machine that is not a handset. Answering opened there would let a
    // test about playing pass where nothing can play.
    FakeBridge::enable();

    $why = new Player()->open(aTitleAtTheDoor())->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyThePlayerDidNotOpen $why): ArrayObject => new ArrayObject([$why]),
    );

    expect($why->getArrayCopy())->toBe([WhyThePlayerDidNotOpen::NoPlayerHere]);
});

it('reads a word it does not know as there being no player here', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Player.Open', ['outcome' => 'refused', 'because' => 'later']);

    $why = new Player()->open(aTitleAtTheDoor())->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened']),
        refused: static fn(WhyThePlayerDidNotOpen $why): ArrayObject => new ArrayObject([$why]),
    );

    expect($why->getArrayCopy())->toBe([WhyThePlayerDidNotOpen::NoPlayerHere]);
});

it('says nothing of the grant where the title is printed', function (): void {
    // A dump, a log context or a failing assertion prints what a value says
    // about itself; the grant is not part of that.
    $printed = print_r(aTitleAtTheDoor(), return: true);

    expect($printed)->not->toContain('the-members-grant')
        ->and($printed)->toContain('(withheld)')
        ->and($printed)->toContain('https://door.example:8443/videos/1/master.m3u8')
        ->and(var_export(aTitleAtTheDoor()->__debugInfo(), return: true))->not->toContain('the-members-grant');
});

it('hands a command the device its word, its position and its track', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Player.Command', ['outcome' => 'done']);

    expect(new Player()->command(PlayerCommand::Seek, 90.25, 'none'))->toBeTrue()
        ->and(whatThePlayerWasHanded($bridge, 'Lemonfiber.Player.Command'))
        ->toBe([['command' => 'seek', 'seconds' => 90.25, 'track' => 'none']]);
});

it('hands a command with nothing beside it nought and no track', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Player.Command', ['outcome' => 'done']);

    new Player()->command(PlayerCommand::Pause);

    expect(whatThePlayerWasHanded($bridge, 'Lemonfiber.Player.Command'))
        ->toBe([['command' => 'pause', 'seconds' => 0, 'track' => '']]);
});

it('reads a command with no player on screen as nothing done', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Player.Command', ['outcome' => 'refused', 'because' => 'no_player']);

    expect(new Player()->command(PlayerCommand::Play))->toBeFalse();
});

it('asks the device to close the player', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Player.Close', ['outcome' => 'closed']);

    new Player()->close();

    expect(whatThePlayerWasHanded($bridge, 'Lemonfiber.Player.Close'))->toBe([[]]);
});

it('reads everything the device says about where the player stands', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Player.State', [
        'outcome' => 'said',
        'stands' => 'stopped',
        'position' => 61.5,
        'duration' => 5400,
        'audio' => [['id' => '0:0', 'language' => 'en', 'label' => 'English']],
        'subtitles' => [['id' => '2:1', 'language' => 'nl', 'label' => 'Nederlands']],
        'chosen_audio' => '0:0',
        'chosen_subtitle' => '2:1',
        'why' => 'pin_mismatch',
    ]);

    $state = new Player()->state();

    expect($state->stands)->toBe(WherePlaybackStands::Stopped)
        ->and($state->position)->toBe(61.5)
        ->and($state->duration)->toBe(5400.0)
        ->and($state->audio)->toEqual([new OfferedTrack('0:0', 'en', 'English')])
        ->and($state->subtitles)->toEqual([new OfferedTrack('2:1', 'nl', 'Nederlands')])
        ->and($state->chosenAudio)->toBe('0:0')
        ->and($state->chosenSubtitle)->toBe('2:1')
        ->and($state->why)->toBe(WhyPlaybackStopped::PinMismatch)
        ->and(whatThePlayerWasHanded($bridge, 'Lemonfiber.Player.State'))->toBe([[]]);
});

it('reads an empty choice and an empty reason as none', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Player.State', [
        'outcome' => 'said',
        'stands' => 'playing',
        'position' => 3,
        'duration' => 0,
        'audio' => [],
        'subtitles' => [],
        'chosen_audio' => '',
        'chosen_subtitle' => '',
        'why' => '',
    ]);

    $state = new Player()->state();

    expect($state->stands)->toBe(WherePlaybackStands::Playing)
        ->and($state->position)->toBe(3.0)
        ->and($state->chosenAudio)->toBeNull()
        ->and($state->chosenSubtitle)->toBeNull()
        ->and($state->why)->toBeNull();
});

it('reads a state with nothing in it as closed and at the start', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Player.State', ['outcome' => 'said']);

    $state = new Player()->state();

    expect($state->stands)->toBe(WherePlaybackStands::Closed)
        ->and($state->position)->toBe(0.0)
        ->and($state->duration)->toBe(0.0)
        ->and($state->audio)->toBe([])
        ->and($state->subtitles)->toBe([])
        ->and($state->chosenAudio)->toBeNull()
        ->and($state->chosenSubtitle)->toBeNull()
        ->and($state->why)->toBeNull();
});

it('reads no answer at all as no player on screen', function (): void {
    FakeBridge::enable();

    $state = new Player()->state();

    expect($state->stands)->toBe(WherePlaybackStands::Closed)
        ->and($state->position)->toBe(0.0)
        ->and($state->duration)->toBe(0.0)
        ->and($state->audio)->toBe([])
        ->and($state->subtitles)->toBe([])
        ->and($state->chosenAudio)->toBeNull()
        ->and($state->chosenSubtitle)->toBeNull()
        ->and($state->why)->toBeNull();
});

it('reads an answer with another outcome as no player on screen, whatever else it holds', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Player.State', [
        'outcome' => 'refused',
        'stands' => 'playing',
        'position' => 12,
        'duration' => 40,
        'audio' => [['id' => '0:0', 'language' => 'en', 'label' => 'English']],
        'chosen_audio' => '0:0',
        'why' => 'refused',
    ]);

    $state = new Player()->state();

    expect($state->stands)->toBe(WherePlaybackStands::Closed)
        ->and($state->position)->toBe(0.0)
        ->and($state->duration)->toBe(0.0)
        ->and($state->audio)->toBe([])
        ->and($state->chosenAudio)->toBeNull()
        ->and($state->why)->toBeNull();
});
