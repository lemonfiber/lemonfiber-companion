<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `keys` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum KeysField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The stack's sentence on what a purpose in the listing is worth. */
    case Purposes = 'purposes';

    /** When a key was minted. */
    case Minted = 'minted';

    /** Whether a household member minted a key for themselves. */
    case MemberMinted = 'member_minted';
}
