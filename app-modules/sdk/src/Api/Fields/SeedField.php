<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `seed` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SeedField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether drift could be judged against the record of what lemonfiber last wrote. */
    case Assessment = 'assessment';

    /** Every connection a run attempted, and how each turned out. */
    case Wirings = 'wirings';

    /** What one connection connects, in the stack's words. */
    case Connection = 'connection';

    /** What a connection that breaks the stack breaks. */
    case Breakage = 'breakage';

    /** What would put a broken connection right. */
    case Remediation = 'remediation';

    /** What the service holds, beside what lemonfiber would write. */
    case Yours = 'yours';
}
