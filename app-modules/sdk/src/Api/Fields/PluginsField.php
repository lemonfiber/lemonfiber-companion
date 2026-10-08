<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `plugins` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum PluginsField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** What an install would do, or did, where the answer is about one. */
    case Install = 'install';

    /** A plugin's id, the name it is installed under. */
    case Plugin = 'plugin';

    /** The commit a plugin was installed at, where it came from a git source. */
    case Revision = 'revision';

    /** The key that signed a plugin, where a catalogue vouched for it. */
    case Signed = 'signed';

    /** What a plugin declared about itself when it was installed. */
    case Declared = 'declared';

    /** Whether anybody reviewed a plugin before it was installed. */
    case Reviewed = 'reviewed';

    /** Every recipe a plugin declares. */
    case Recipes = 'recipes';

    /** Every value a recipe could carry, and where to. */
    case Pairs = 'pairs';

    /** The method a recipe's call is made with. */
    case Method = 'method';

    /** The adapter of lemonfiber's a call reaches its destination through. */
    case Adapter = 'adapter';

    /** What approving one value's carriage is written as. */
    case Approval = 'approval';

    /** Why a value is released away from the service it was read from. */
    case Release = 'release';

    /** Every proof that has to hold before a plugin is installed. */
    case Proofs = 'proofs';

    /** A proof's id, which its verdict is reported against. */
    case Proof = 'proof';

    /** What asking a proof came to; absent where it was not asked. */
    case CameTo = 'came_to';

    /** What a proof establishes, in one line. */
    case Establishes = 'establishes';

    /** What a proof asks, as the method and the path. */
    case Asks = 'asks';

    /** Each way a proof did not hold. */
    case Faults = 'faults';

    /** What the stack's own checks made of an install. */
    case Verified = 'verified';

    /** Every check an install made worse. */
    case Broke = 'broke';

    /** Every check nothing could be concluded about. */
    case Unsettled = 'unsettled';

    /** Every ask an install would leave contested. */
    case Contests = 'contests';

    /** Every bundled setting a plugin declares it will change. */
    case Overrides = 'overrides';

    /** One bundled setting a plugin changes. */
    case Setting = 'setting';

    /** What a change puts at its path. */
    case Puts = 'puts';

    /** How each installed plugin's source stands, asked now. */
    case Sources = 'sources';
}
