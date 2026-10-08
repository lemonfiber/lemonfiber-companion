<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `update` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum UpdateField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /**
     * Whether one of those changes cannot be put back.
     *
     * A property of the change rather than of the release: an update can move
     * four services and be undoable for three of them, so this is read per
     * change and the services it is true of are named on their own.
     */
    case Irreversible = 'irreversible';

    /** The releases inside the changelog, newest as the stack ordered them. */
    case Releases = 'releases';

    /** How one service's share of an applied update finished. */
    case Ending = 'ending';
}
