<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function implode;

/**
 * The keys this module keeps things under in the platform's secure store.
 *
 * One set rather than a prefix in each adapter, because the store is shared
 * and two adapters writing under one key would each read the other's record as
 * a shape it does not know. As cases of one enum no two can be spelled alike.
 * An adapter that keeps one record per stack, or per key, names it beneath its
 * own, separated by dots.
 */
enum KeptUnder: string
{
    /** One session per paired stack. */
    case Session = 'lemonfiber.session';

    /** The keys the seal seals with. */
    case SealKeys = 'lemonfiber.seal';

    /** The whole list of paired stacks, as one record. */
    case Stacks = 'lemonfiber.stacks';

    /** The last word each stack said about how it stands, as one record. */
    case Standings = 'lemonfiber.standings';

    /** A handle to work a stack was left carrying, per kind of work and per stack. */
    case WorkLeftRunning = 'lemonfiber.left-running';

    /** The stack the operator was last on, and the tab they last used on each. */
    case WhereTheOperatorWas = 'lemonfiber.where-left-off';

    /** The key for one record beneath this one: the names in order, after a dot each. */
    public function beneath(string ...$names): string
    {
        return implode('.', [$this->value, ...$names]);
    }
}
