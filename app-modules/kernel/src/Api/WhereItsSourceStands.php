<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the stack reached the source a plugin came from, tried and could not, or did not ask it.
 *
 * The three the stack says are its own words. The fourth is a plugin the
 * answer said nothing of, which the stack says by leaving it out, and which
 * no reader takes from the wire.
 */
enum WhereItsSourceStands: string
{
    case Reachable = 'reachable';
    case Unreachable = 'unreachable';
    case Unasked = 'unasked';
    case NotSaid = 'not_said';

    /** How it stands, as a catalogue key, or empty where nothing was said. */
    public function saidOnTheScreen(): string
    {
        return $this === self::NotSaid ? '' : sprintf('plugins.source.%s', $this->value);
    }
}
