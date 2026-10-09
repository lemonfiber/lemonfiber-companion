<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use function floor;

use Lemonfiber\Native\Player\Player;
use Lemonfiber\Native\Player\TitleAtTheDoor;
use Lemonfiber\Native\Player\WherePlaybackStands;
use Lemonfiber\Native\Player\WhyPlaybackStopped;
use Lemonfiber\Native\Player\WhyThePlayerDidNotOpen;
use Modules\Kernel\Api\ATitleToPlay;
use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\WhatOpeningCameTo;
use Modules\Kernel\Api\WherePlayingStands;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * The device's own player, handed what the core stated.
 *
 * Nothing about the door is decided here: the native half reads every field
 * again and refuses what does not hold, pins the door's certificate, and
 * carries the grant in a header and nowhere else. The languages the member
 * chose are handed over as the player's own words for them, and the player
 * matches them against the tracks it finds.
 */
final readonly class PlatformPlayer implements Playing
{
    public function __construct(private Player $player) {}

    public function open(ATitleToPlay $title): WhatOpeningCameTo
    {
        return $this->player->open(new TitleAtTheDoor(
            location: $title->location()->forThePlayer(),
            fingerprint: $title->door()->forThePlayer(),
            grant: $title->grant()->forTheDoor(),
            startAt: $title->startAt()->seconds(),
            title: $title->named(),
            audio: $this->heard($title->languages()->hear()),
            subtitle: $this->read($title->languages()->read()),
        ))->either(
            opened: static fn(): WhatOpeningCameTo => WhatOpeningCameTo::opened(),
            refused: static fn(WhyThePlayerDidNotOpen $why): WhatOpeningCameTo => WhatOpeningCameTo::refused(
                $why === WhyThePlayerDidNotOpen::NoPlayerHere
                    ? WhyPlayingDidNotStart::ThereIsNoPlayerHere
                    : WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed,
            ),
        );
    }

    public function whereItStands(): WherePlayingStands
    {
        $state = $this->player->state();

        return WherePlayingStands::of(
            $this->meaning($state->stands, $state->why),
            HowFarIn::at((int) floor($state->position)),
        );
    }

    public function close(): WherePlayingStands
    {
        $this->player->close();

        return $this->whereItStands();
    }

    /** Where the device says playback stands, in the terms this application reasons in. */
    private function meaning(WherePlaybackStands $stands, ?WhyPlaybackStopped $why): PlaybackIs
    {
        return match ($stands) {
            WherePlaybackStands::Opening => PlaybackIs::Opening,
            WherePlaybackStands::Playing => PlaybackIs::Playing,
            WherePlaybackStands::Paused => PlaybackIs::Paused,
            WherePlaybackStands::Stalled => PlaybackIs::Stalled,
            WherePlaybackStands::Ended => PlaybackIs::Ended,
            WherePlaybackStands::Closed => PlaybackIs::Closed,
            WherePlaybackStands::Stopped => match ($why) {
                WhyPlaybackStopped::PinMismatch => PlaybackIs::StoppedByThePin,
                WhyPlaybackStopped::UnsupportedFormat => PlaybackIs::StoppedOnTheFormat,
                WhyPlaybackStopped::Refused => PlaybackIs::StoppedAtTheDoor,
                // A stop the device gave no reason for reads as the door out of
                // reach, which is the one whose remedy is to try again.
                WhyPlaybackStopped::Unreachable, null => PlaybackIs::StoppedOutOfReach,
            },
        };
    }

    /** The language to hear, in the player's words: a tag, or no preference for the title's own. */
    private function heard(HearIn $hear): string
    {
        return match ($hear) {
            HearIn::TheOriginal => TitleAtTheDoor::NO_LANGUAGE,
            HearIn::Dutch, HearIn::English => $hear->value,
        };
    }

    /** The language to read, in the player's words: a tag, or none. */
    private function read(ReadIn $read): string
    {
        return match ($read) {
            ReadIn::Nothing => TitleAtTheDoor::NO_LANGUAGE,
            ReadIn::Dutch, ReadIn::English => $read->value,
        };
    }
}
