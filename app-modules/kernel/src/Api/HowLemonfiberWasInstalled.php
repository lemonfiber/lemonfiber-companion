<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How the running copy of lemonfiber got onto its machine, as the stack tells it.
 *
 * What decides who can replace it: a copy a package manager put there is that
 * tool's to replace. *Could not tell* is a case of its own and is never read as
 * one lemonfiber can replace.
 */
enum HowLemonfiberWasInstalled: string
{
    case Homebrew = 'homebrew';
    case Scoop = 'scoop';
    case Winget = 'winget';
    case Cargo = 'cargo';

    /** A distribution's own package manager. */
    case Distribution = 'distribution';

    /** lemonfiber's own installer. */
    case Installer = 'installer';

    /** Somewhere the stack recognises as none of the above. */
    case Elsewhere = 'elsewhere';

    /** The stack could not tell. */
    case Untellable = 'untellable';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.itself.installed.%s', $this->value);
    }
}
