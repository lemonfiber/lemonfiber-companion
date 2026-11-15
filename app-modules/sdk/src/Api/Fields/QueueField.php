<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `queue` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum QueueField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether healing acts, or only classifies. */
    case Healing = 'healing';

    /** How many passes in a row flag an item before it is acted on. */
    case StrikesNeeded = 'strikes_needed';

    /** When the latest pass classified the queue. */
    case ClassifiedAt = 'classified_at';

    /** Where a download client files a curator's downloads outside the curator's folder. */
    case Mismatch = 'mismatch';

    /** Every curator or download client the latest pass could not reach or verify. */
    case Unreachable = 'unreachable';

    /** The newest runs first. */
    case Runs = 'runs';

    /** Why an item is unhealthy. */
    case Classification = 'class';

    /** How many passes in a row have flagged an item. */
    case Strikes = 'strikes';

    /** When an item was added. */
    case AddedAt = 'added_at';

    /** Until when an item is left alone as newly added. */
    case InGraceUntil = 'in_grace_until';

    /** Each act a run took on an item, in order. */
    case Acts = 'acts';

    /** One act. */
    case Act = 'act';
}
