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

    /** The services, as opposed to this copy of lemonfiber. */
    case TheStack = 'stack';

    /**
     * Whether one of those changes cannot be put back.
     *
     * A property of the change rather than of the release: an update can move
     * four services and be undoable for three of them, so this is read per
     * change and the services it is true of are named on their own.
     */
    case Irreversible = 'irreversible';

    /** Where the releases a stack could be on are listed. */
    case Changelog = 'changelog';

    /** The releases inside the changelog, newest as the stack ordered them. */
    case Releases = 'releases';

    /** What one release is called. */
    case Version = 'version';

    /**
     * What a release delivers, in the stack's own prose.
     *
     * Optional on the wire, and a release the generator had nothing to say
     * about is a state rather than a defect — read into the two arms of
     * {@see \Modules\Kernel\Api\WhatAReleaseDelivers} so that nothing said
     * and nothing to print cannot be confused on a row.
     */
    case Delivers = 'delivers';

    /** Whether somebody in the house would notice this release. */
    case UserFacing = 'user_facing';

    /** How one service's share of an applied update finished. */
    case Ending = 'ending';

    /**
     * The operator's yes, on the actions that only describe themselves or hold
     * back without one: the update, an invitation, confirming a held quality
     * choice, and upgrading what is already in the library.
     *
     * Here rather than in `WireField`, which holds the words read out of more
     * than one envelope. This one is sent and never read, and a word is named
     * once, so it stays with the first action that sent it.
     */
    case Confirm = 'confirm';
}
