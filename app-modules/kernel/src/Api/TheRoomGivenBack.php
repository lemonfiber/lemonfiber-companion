<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What giving back room came to: the room returned, how many items went, and why each one left behind stayed.
 */
final readonly class TheRoomGivenBack
{
    private function __construct(private int $bytes, private int $went, private WhyItemsStayed $left) {}

    /** The stack's report, each figure refused below nothing. */
    public static function reported(int $bytes, int $went, WhyItemsStayed $left): self
    {
        if ($bytes < 0) {
            throw RoomSaysNothing::negative('bytes', $bytes);
        }

        if ($went < 0) {
            throw RoomSaysNothing::negative('gone', $went);
        }

        return new self($bytes, $went, $left);
    }

    /** The room returned, in bytes. */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /** How many items went. */
    public function went(): int
    {
        return $this->went;
    }

    /** The stack's reason for each item left behind. */
    public function left(): WhyItemsStayed
    {
        return $this->left;
    }
}
