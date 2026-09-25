<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `migration` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum MigrationField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether the engine answered at all, which is what tells an empty machine from an unread one. */
    case Read = 'read';

    /** Every host port an existing service publishes. */
    case Ports = 'ports';

    /** Whether lemonfiber knows an existing service and could take it over as it stands. */
    case Adoptable = 'adoptable';

    /** Ports lemonfiber wants that something already here holds. */
    case Conflicts = 'conflicts';

    /** The host port a conflict is about. */
    case Port = 'port';

    /** The lemonfiber service that would publish a port already held. */
    case WantedBy = 'wanted_by';

    /** The project already holding it. */
    case HeldBy = 'held_by';

    /** What may be done about what was found, least destructive first. */
    case Modes = 'modes';

    /** The word an operator types for one mode. */
    case Mode = 'mode';

    /** Whether a mode is offered already chosen. */
    case Preselected = 'preselected';

    /** What the existing layout costs where it cannot hold a hardlink, absent where it can. */
    case Linking = 'linking';

    /** The filesystems the existing setup keeps its data on. */
    case Filesystems = 'filesystems';

    /** What adopting each recognised service would come to. */
    case Carrying = 'carrying';

    /** Whether a service's database must be backed up before lemonfiber opens it. */
    case BackupFirst = 'backup_first';

    /** What no migration carries across, whatever mode it runs in. */
    case NotCarried = 'not_carried';
}
