<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `clients` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ClientsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Every kind of device, in the order somebody is likely to be holding one. */
    case Devices = 'devices';

    /** What somebody would call the device they are holding. */
    case Device = 'device';

    /** What to use on it. */
    case Client = 'client';

    /** How well it is served. */
    case Support = 'support';

    /** Where every device works, said once. */
    case OnlyAtHome = 'only_at_home';

    /** What this will not do for them. */
    case NothingIsInstalled = 'nothing_is_installed';

    /** Why playback here is likely to struggle before any app is chosen. */
    case Straining = 'straining';

    /** What to do when it does not work, keyed by the symptom. */
    case Trouble = 'trouble';

    /** What somebody says is happening. */
    case Symptom = 'symptom';

    /** What is likely behind a symptom, most likely first. */
    case Causes = 'causes';

    /** How to tell one cause from the others under the same symptom. */
    case Tell = 'tell';

    /** What to do about one cause. */
    case Fix = 'fix';
}
