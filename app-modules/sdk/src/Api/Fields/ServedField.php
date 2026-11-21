<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `served` envelope that no other envelope this module reads carries.
 */
enum ServedField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether the surface is encrypted. */
    case Encrypted = 'encrypted';

    /** Whether the surface is offered to the network. */
    case Network = 'network';

    /** Why where it is served could not be written down. */
    case Unrecorded = 'unrecorded';

    /** Whom the certificate was made out to. */
    case Subject = 'subject';

    /** Whether the subject was cut short. */
    case SubjectCut = 'subject_cut';

    /** Who made the certificate out. */
    case Issuer = 'issuer';

    /** Whether the issuer was cut short. */
    case IssuerCut = 'issuer_cut';

    /** Whether the certificate signed itself. */
    case SelfSigned = 'self_signed';

    /** The names the certificate covers, as many as were written out. */
    case Names = 'names';

    /** How many names were left out. */
    case NamesMore = 'names_more';

    /** When the certificate is valid from. */
    case ValidFrom = 'valid_from';

    /** When the certificate is valid until. */
    case ValidUntil = 'valid_until';
}
