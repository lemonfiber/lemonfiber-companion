<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where a volume stands, or a machine as a whole, which stands where its worst volume does.
 *
 * The middle three are read by the stack off what will be free once what is
 * already on its way has landed, not off what is free now: a volume with room
 * today and more than that queued is going to fill.
 *
 * **Could not be read is a case of its own**, and never a comfortable one: a
 * volume nobody could read and a volume with room to spare are opposite things
 * to somebody deciding whether to act.
 */
enum WhereTheRoomStands: string
{
    /** How full it is could not be determined. */
    case Unknown = 'unknown';

    /** Comfortable headroom. */
    case Ample = 'ample';

    /** Below comfortable, not urgent. */
    case Advisory = 'advisory';

    /** Going to fill with what is on its way. */
    case Warning = 'warning';

    /** Nearly full. */
    case Critical = 'critical';

    /** Full: the stack has stopped starting new downloads. */
    case Exhausted = 'exhausted';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.room.level.%s', $this->value);
    }
}
