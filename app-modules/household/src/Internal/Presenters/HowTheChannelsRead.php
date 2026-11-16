<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Design\Api\ATap;
use Modules\Household\Internal\ViewModels\WhatOneChannelSays;
use Modules\Household\Internal\ViewModels\WhatTheChannelsTurnedOutToBe;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\Zone;

/**
 * The channels a member may watch, as Home's Live TV shelf draws them, in the core's order.
 *
 * When a programme ends is read on the phone's own clock, to the minute. A
 * channel the guide says nothing of now is drawn with its name alone.
 */
final readonly class HowTheChannelsRead
{
    /** What pressing a channel calls on the screen. */
    public const string TUNES = 'tuneTo';

    public function __construct(private Zone $here) {}

    public function these(TheChannels $channels): WhatTheChannelsTurnedOutToBe
    {
        $drawn = [];

        foreach ($channels as $channel) {
            $drawn[] = $channel->now(
                showing: fn(AProgramme $now): WhatOneChannelSays => $this->drawn($channel, $now->title(), $this->until($now), $now->certificate()),
                nothingSaid: fn(): WhatOneChannelSays => $this->drawn($channel, '', '', ''),
            );
        }

        return WhatTheChannelsTurnedOutToBe::these($drawn);
    }

    private function drawn(AChannel $channel, string $programme, string $until, string $certificate): WhatOneChannelSays
    {
        return new WhatOneChannelSays(
            id: $channel->id()->named(),
            name: $channel->name(),
            number: $channel->number(),
            programme: $programme,
            until: $until,
            certificate: $certificate,
            tap: ATap::calling(self::TUNES, $channel->id()->named())->written(),
        );
    }

    private function until(AProgramme $now): string
    {
        return $now->ends()->either(
            at: fn(Instant $at): InWords => new InWords($this->here->timeOfDayAt($at)->shownToTheMinute()),
            unsaid: static fn(): InWords => new InWords(''),
        )->said;
    }
}
