<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One volume the stack watches, as one reading measured it.
 *
 * What is free, the volume's limit and what will be free once what is on its
 * way has landed are each {@see AnAmountOfRoom}, which has a case for a figure
 * the stack could not read. What is on its way is always a figure: the stack
 * counts it from its own queue.
 */
final readonly class AVolume
{
    private function __construct(
        private WhatAVolumeHolds $holds,
        private string $point,
        private AnAmountOfRoom $free,
        private AnAmountOfRoom $limit,
        private int $committed,
        private AnAmountOfRoom $projected,
        private WhereTheRoomStands $stands,
        private HowFreshAReadingIs $reading,
    ) {}

    /** A volume, mounted at `$point`, with every figure the reading carries. */
    public static function measured(
        WhatAVolumeHolds $holds,
        string $point,
        AnAmountOfRoom $free,
        AnAmountOfRoom $limit,
        int $committed,
        AnAmountOfRoom $projected,
        WhereTheRoomStands $stands,
        HowFreshAReadingIs $reading,
    ): self {
        if ($committed < 0) {
            throw RoomSaysNothing::negative('committed', $committed);
        }

        return new self($holds, $point, $free, $limit, $committed, $projected, $stands, $reading);
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

    /** Bytes free now. */
    public function free(): AnAmountOfRoom
    {
        return $this->free;
    }

    /** The volume's own size, or the quota where it has one. */
    public function limit(): AnAmountOfRoom
    {
        return $this->limit;
    }

    /** Bytes already on their way to landing here. */
    public function committed(): int
    {
        return $this->committed;
    }

    /** What will be free once what is on its way has landed. */
    public function projected(): AnAmountOfRoom
    {
        return $this->projected;
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
