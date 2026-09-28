<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `stop-seeding` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's, and the word the `stop-seeding`
 * action is asked with.
 */
enum StopSeedingField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Which completed download, asked by name and answered with where it stands. */
    case Download = 'download';

    /** What goes with it, in the stack's words. */
    case Goes = 'goes';
}
