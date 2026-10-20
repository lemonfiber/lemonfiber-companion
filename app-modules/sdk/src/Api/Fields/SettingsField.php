<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `settings` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum SettingsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every secret a service's environment names, never with its value. */
    case Secrets = 'secrets';

    /** Each service that reads a setting or a secret. */
    case Readers = 'readers';

    /** The variable a service reads a setting or a secret into. */
    case Variable = 'variable';

    /** What a service's manifest declares while no value is recorded. */
    case Default = 'default';

    /** The namespace a setting or a secret belongs to, where it is not the core's. */
    case Namespace = 'namespace';

    /** Every service whose entry a change regenerates. */
    case Regenerates = 'regenerates';

    /** Every running service a change restarts. */
    case Restarts = 'restarts';

    /** Why the services a change restarts were not restarted, where they were not. */
    case Unrestarted = 'unrestarted';
}
