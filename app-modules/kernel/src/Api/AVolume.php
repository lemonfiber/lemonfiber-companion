<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One volume the stack watches, as one reading measured it.
 *
 * Which of the two it is, where it is mounted, its figures, where it stands,
 * and how far those figures can be relied on.
 */
final readonly class AVolume
{
    private function __construct(
        private WhatAVolumeHolds $holds,
        private string $point,
        private HowMuchRoomAVolumeHas $room,
        private WhereTheRoomStands $stands,
        private HowFreshAReadingIs $reading,
    ) {}

    /** A volume, mounted at `$point`, with every figure the reading carries. */
    public static function measured(
        WhatAVolumeHolds $holds,
        string $point,
        HowMuchRoomAVolumeHas $room,
        WhereTheRoomStands $stands,
        HowFreshAReadingIs $reading,
    ): self {
        return new self($holds, $point, $room, $stands, $reading);
    }

    /** Which of the two volumes this is. */
    public function holds(): WhatAVolumeHolds
    {
        return $this->holds;
    }

    /**
     * Where the volume is mounted, which is what its limit belongs to.
     *
     * Blank where the stack could not attribute the path to any mount, which is
     * also when every figure but what is on its way is unread.
     */
    public function point(): string
    {
        return $this->point;
    }

    /** What is free, its limit, what is on its way, and what will be free after. */
    public function room(): HowMuchRoomAVolumeHas
    {
        return $this->room;
    }

    /** Where it stands. */
    public function stands(): WhereTheRoomStands
    {
        return $this->stands;
    }

    /** How far its figures can be relied on. */
    public function reading(): HowFreshAReadingIs
    {
        return $this->reading;
    }
}
