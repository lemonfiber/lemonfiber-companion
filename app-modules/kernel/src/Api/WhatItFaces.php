<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What a service published to the household network is to the people in the house.
 */
enum WhatItFaces: string
{
    /** Where asking for something begins. */
    case Asking = 'asking';

    /** The library, where what arrived is watched. */
    case Watching = 'watching';

    /** One kind of media, reached from the library. */
    case Shelf = 'shelf';

    /** An index over every service, including the ones the house should not learn exist. */
    case Operators = 'operators';

    /** How the others are reached, rather than one of them. */
    case Carriage = 'carriage';

    /** Published to the household, with nothing saying what it is to them. */
    case Unstated = 'unstated';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.front_door.facing.%s', $this->value);
    }
}
