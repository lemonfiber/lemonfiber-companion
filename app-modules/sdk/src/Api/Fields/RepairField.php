<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `repair` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum RepairField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The name a stack gives one listing of repairs, quoted back on a yes. */
    case Agreement = 'agreement';

    /** What one repair would do, said in the stack's own words. */
    case Does = 'does';

    /** What else that repair touches on its way. */
    case Effects = 'effects';

    /** The repairs a stack says it would carry out. */
    case Offered = 'offered';

    /** What became of each repair a stack was agreed to carry out. */
    case Mended = 'mended';

    /** What a repair left on the machine where it stopped part-way. */
    case Leaving = 'leaving';

    /** The repair one outcome is about, inside a record of what was done. */
    case Repair = 'repair';

    /** Whether a repair can be taken back afterwards. */
    case Reversible = 'reversible';
}
