<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `restore` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's, and the words the `restore`
 * action is asked with.
 */
enum RestoreField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Which copy to put back, by the name the listing of copies gave it. */
    case Archive = 'archive';

    /** The listing a yes is given for, quoted back by its name. */
    case Offer = 'offer';

    /** The operator's yes to putting the copy's data somewhere other than where it came from. */
    case Repoint = 'repoint';

    /** What putting the copy back would do, read before anything is overwritten. */
    case Would = 'would';

    /** What putting it back did, absent where nothing was put back. */
    case Done = 'done';

    /** The copy's own account of itself. */
    case Manifest = 'manifest';

    /** When the copy was taken. */
    case CreatedAt = 'created_at';

    /** The version of lemonfiber that took it, in the copy's own account. */
    case ProductVersion = 'product_version';

    /** What one thing the copy holds is, in the operator's terms. */
    case Label = 'label';

    /** Whether the copy comes from an older major version. */
    case Downgrade = 'downgrade';

    /** Where the data would go instead of where it came from, before a yes. */
    case Relocation = 'relocation';

    /** Where the data went instead of where it came from, after one. */
    case Relocated = 'relocated';

    /** The data root the copy was taken against. */
    case Was = 'was';

    /** The data root it goes to on this machine. */
    case Now = 'now';

    /** The version of lemonfiber that took the copy, on a restore's report. */
    case FromVersion = 'from_version';
}
