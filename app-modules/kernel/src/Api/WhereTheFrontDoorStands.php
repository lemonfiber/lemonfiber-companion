<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where the household's one front door stands.
 *
 * *Unreachable* and *stranded* are both a door nobody can use, fixed at
 * opposite ends: the first by starting the service, the second by giving the
 * machine an address the household can reach.
 */
enum WhereTheFrontDoorStands: string
{
    /** The door is where asking begins, and it is running. */
    case Established = 'established';

    /** There is nothing to ask for, so the library is the door. */
    case LibraryOnly = 'library-only';

    /** There is a door and it is not answering. */
    case Unreachable = 'unreachable';

    /** There is a door, it is answering, and nothing can say where another device would reach it. */
    case Stranded = 'stranded';

    /** Nothing is published to the household at all. */
    case None = 'none';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.front_door.standing.%s', $this->value);
    }
}
