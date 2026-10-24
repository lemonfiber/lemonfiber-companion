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

    /** Why a value is released away from the service it was read from. */
    case Release = 'release';

    /** Every proof that has to hold before a plugin is installed. */
    case Proofs = 'proofs';

    /** A proof's id, which its verdict is reported against; and, on the envelope, what proving a plugin again came to. */
    case Proof = 'proof';

    /** Whether proving a plugin again cleared every answer kept against it. */
    case Cleared = 'cleared';

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

    /** What a change puts at its path. */
    case Puts = 'puts';

    /** How each installed plugin's source stands, asked now. */
    case Sources = 'sources';

    /** An update's own account, where the answer is about one. */
    case Update = 'update';

    /** Every service that stops for an update or a removal, named before any does. */
    case Interrupts = 'interrupts';

    /** What putting the installed version's changes back came to, or would. */
    case WentBack = 'went_back';

    /** Where an update did not hold, what putting the version it replaced back came to. */
    case Restored = 'restored';

    /** Whether everything the version put back placed is on the machine again. */
    case Placed = 'placed';

    /** Every capability a removal would leave with nothing filling it. */
    case Leaves = 'leaves';

    /** The plugin filling a capability now, which is the one going. */
    case FilledBy = 'filled_by';

    /** Every answer an installed plugin's adapter gave outside its contract. */
    case Nonconforming = 'nonconforming';

    /** Every service an install would have take a privileged shape. */
    case Taking = 'taking';

    /** Each credential of another service the operator granted a third-party plugin. */
    case Granted = 'granted';

    /** Each credential of another service an install or update would hand a third-party plugin, asked for apart from the offer. */
    case Granting = 'granting';

    /** When a recipe runs: inside an install and an update, or only when asked for by name. */
    case On = 'on';

    /** Every value a recipe asks the operator for when it runs. */
    case Inputs = 'inputs';

    /** What a recipe asks the operator for, in the manifest's words. */
    case Ask = 'ask';

    /** Running one of a plugin's demand recipes: what it would do, and once run, what it came to. */
    case Demand = 'demand';

    /** The recipe a run is about, and, inside what it came to, that recipe's id. */
    case Recipe = 'recipe';

    /** What running a demand recipe came to. */
    case Ran = 'ran';

    /** What one step of a recipe that ran came to. */
    case Came = 'came';
}
