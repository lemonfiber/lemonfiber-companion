<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use Lemonfiber\Native\Call;
use Lemonfiber\Native\WhatAnAnswerHolds;
use Lemonfiber\Native\WhatTheBridgeAnswered;

/**
 * The device's own player, over the household's front door.
 *
 * The PHP face of `PlayerFunctions` in Kotlin and Swift. Open a title, command
 * it, close it, and ask where it stands. Everything that decides whether a
 * byte may be fetched is on the device: the door, the pin, the grant in a
 * header, the playlists read before the player sees them. What is here is
 * handing over what the core stated and reading the answers.
 *
 * **Off a handset there is no player.** Opening answers that none answered,
 * every command answers that nothing was done, and the state is closed — the
 * least each can say, so that no test about playing passes on a machine that
 * cannot play.
 */
final readonly class Player
{
    /** Put the player on screen for one title, or say why not. */
    public function open(TitleAtTheDoor $title): Opening
    {
        $said = WhatTheBridgeAnswered::to(Call::PlayerOpen, $title->asCarried());

        return HowThePlayerAnswered::in($said) === HowThePlayerAnswered::Showing
            ? Opening::opened()
            : Opening::refused(WhyThePlayerDidNotOpen::orNoPlayerHere($said->word(WhatAnAnswerHolds::Because)));
    }

    /**
     * Ask one thing of the player on screen, and answer whether there was one to ask.
     *
     * @param float  $seconds where to go, for a seek.
     * @param string $track   which track, for a change of sound or subtitles.
     */
    public function command(PlayerCommand $command, float $seconds = 0.0, string $track = ''): bool
    {
        $said = WhatTheBridgeAnswered::to(Call::PlayerCommand, [
            'command' => $command->value,
            'seconds' => $seconds,
            'track' => $track,
        ]);

        return HowThePlayerAnswered::in($said) === HowThePlayerAnswered::Done;
    }

    /** Take the player off screen, whether or not one was on it. */
    public function close(): void
    {
        WhatTheBridgeAnswered::toNothing(Call::PlayerClose);
    }

    /** Where the player stands now. */
    public function state(): WhereThePlayerStands
    {
        $said = WhatTheBridgeAnswered::toNothing(Call::PlayerState);

        if (HowThePlayerAnswered::in($said) !== HowThePlayerAnswered::Said) {
            return new WhereThePlayerStands(WherePlaybackStands::Closed, 0.0, 0.0, [], [], null, null, null);
        }

        return new WhereThePlayerStands(
            stands: WherePlaybackStands::orClosed($said->word(WhatAnAnswerHolds::Stands)),
            position: $said->number(WhatAnAnswerHolds::Position) ?? 0.0,
            duration: $said->number(WhatAnAnswerHolds::Duration) ?? 0.0,
            audio: OfferedTrack::listedIn($said->listed(WhatAnAnswerHolds::Audio)),
            subtitles: OfferedTrack::listedIn($said->listed(WhatAnAnswerHolds::Subtitles)),
            chosenAudio: $this->chosen($said->word(WhatAnAnswerHolds::ChosenAudio)),
            chosenSubtitle: $this->chosen($said->word(WhatAnAnswerHolds::ChosenSubtitle)),
            why: WhyPlaybackStopped::orNone($said->word(WhatAnAnswerHolds::Why)),
        );
    }

    /** A chosen track, or none where the device said none. */
    private function chosen(?string $said): ?string
    {
        return $said === '' ? null : $said;
    }
}
