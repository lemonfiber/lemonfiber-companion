<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A volume's figures: what is free, its limit, what is on its way, and what will be free once that has landed.
 *
 * Three of them are {@see AnAmountOfRoom}, which has a case for a figure the
 * stack could not read. What is on its way is always a figure: the stack
 * counts it from its own queue.
 */
final readonly class HowMuchRoomAVolumeHas
{
    private function __construct(
        private AnAmountOfRoom $free,
        private AnAmountOfRoom $limit,
        private int $committed,
        private AnAmountOfRoom $projected,
    ) {}

    /** The four figures, with what is on its way refused below nothing. */
    public static function counted(AnAmountOfRoom $free, AnAmountOfRoom $limit, int $committed, AnAmountOfRoom $projected): self
    {
        if ($committed < 0) {
            throw RoomSaysNothing::negative('committed', $committed);
        }

        return new self($free, $limit, $committed, $projected);
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
}
