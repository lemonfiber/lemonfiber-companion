<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `watcher` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum WatcherField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The plugin filling the watcher capability. */
    case Watcher = 'watcher';

    /** Whether the watcher answered. */
    case Reachable = 'reachable';

    /** Why it did not. */
    case UnreachableBecause = 'unreachable_because';

    /** When the stack asked it. */
    case ReadAt = 'read_at';

    /** Every monitor it holds. */
    case Monitors = 'monitors';

    /** What no active monitor covers. */
    case Uncovered = 'uncovered';

    /** Since when a monitor has said what it says. */
    case Since = 'since';

    /** When a monitor last took a reading. */
    case LastReading = 'last_reading';

    /** The heartbeat a monitor waits for. */
    case Heartbeat = 'heartbeat';

    /** How often a heartbeat is due, in seconds. */
    case EveryS = 'every_s';

    /** When a heartbeat last arrived. */
    case LastCheckIn = 'last_check_in';

    /** Why a monitor watches something other than it should. */
    case Unconfigured = 'unconfigured';

    /** What such a monitor watches instead. */
    case Watches = 'watches';

    /** An entry point a monitor covers. */
    case EntryPoint = 'entry-point';
}
