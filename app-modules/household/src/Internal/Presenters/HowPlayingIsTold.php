<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * Why a title did not play, or stopped, in the household's words.
 *
 * Told as what a member can do about it, never as what went wrong on the
 * wire: away from home, tell whoever looks after the house, or press Play
 * again. Nothing a member reads here names a door, a certificate or a grant.
 */
final readonly class HowPlayingIsTold
{
    /** The device would not play what the house said. */
    private const string CANNOT_BE_PLAYED = 'household.play.cannot_be_played';

    /** There is no player on this device. */
    private const string NO_PLAYER = 'household.play.no_player';

    /** The library is out of reach from here. */
    private const string OUT_OF_REACH = 'household.play.out_of_reach';

    /** Something other than the house answered. */
    private const string NOT_THE_HOUSE = 'household.play.not_the_house';

    /** This device cannot play what the house sent. */
    private const string NOT_ON_THIS_DEVICE = 'household.play.not_on_this_device';

    /** The house would not let it play, asked twice. */
    private const string NOT_LET_IN = 'household.play.not_let_in';

    /** Why the player did not come on screen, as a catalogue key. */
    public static function notStarted(WhyPlayingDidNotStart $why): string
    {
        return match ($why) {
            WhyPlayingDidNotStart::WhatTheHouseSaidCannotBePlayed => self::CANNOT_BE_PLAYED,
            WhyPlayingDidNotStart::ThereIsNoPlayerHere => self::NO_PLAYER,
        };
    }

    /** Why playback stopped where it could not go on, as a catalogue key; empty where it did not stop. */
    public static function stopped(PlaybackIs $is): string
    {
        return match ($is) {
            PlaybackIs::StoppedOutOfReach => self::OUT_OF_REACH,
            PlaybackIs::StoppedByThePin => self::NOT_THE_HOUSE,
            PlaybackIs::StoppedOnTheFormat => self::NOT_ON_THIS_DEVICE,
            PlaybackIs::StoppedAtTheDoor => self::NOT_LET_IN,
            PlaybackIs::Opening, PlaybackIs::Playing, PlaybackIs::Paused, PlaybackIs::Stalled, PlaybackIs::Ended, PlaybackIs::Closed => '',
        };
    }
}
