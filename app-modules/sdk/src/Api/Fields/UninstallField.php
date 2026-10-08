<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `uninstall` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's, and the words the `uninstall`
 * read is asked with.
 */
enum UninstallField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Which of the four removals: asked for on the read, and answered. */
    case Tier = 'tier';

    /** What a removal takes, in the operator's words. */
    case Removes = 'removes';

    /** What a removal leaves alone, in the operator's words. */
    case Keeps = 'keeps';

    /** What beneath the data location the stack did not put there. */
    case Foreign = 'foreign';

    /** How many files were found under something that is not lemonfiber's. */
    case Files = 'files';

    /** What is still coming down, which stopping would interrupt. */
    case Coming = 'coming';

    /** How far along one download is, from none to a hundred. */
    case Progress = 'progress';

    /** What lemonfiber cannot remove, each with how to remove it by hand. */
    case Outside = 'outside';

    /** How to remove something by hand on this platform. */
    case ByHand = 'by_hand';

    /** Whether this machine was found to have something lemonfiber cannot remove. */
    case Found = 'found';

    /** Whether every source a removal needed answered. */
    case Complete = 'complete';

    /** Whether the data location is on a network share or a drive that unplugs. */
    case Volume = 'volume';

    /** Whether a copy is taken before configuration is destroyed, and how. */
    case Backup = 'backup';

    /** Which of the four sorts of thing one line is. */
    case Sort = 'sort';

    /** Whether anything was removed on this run, and what became of it. */
    case Removal = 'removal';

    /** The credentials a removal destroyed, said rather than left to be inferred. */
    case Credentials = 'credentials';
}
