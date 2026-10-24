<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a secret's value came from, as the stack says it.
 */
enum WhereASecretCameFrom: string
{
    /** The operator gave it. */
    case Operator = 'operator';

    /** A plugin's recipe made it. */
    case Recipe = 'recipe';

    /** It was granted from another service. */
    case Granted = 'granted';

    /** lemonfiber made it for the plugin at install. */
    case Minted = 'minted';

    /** The stack said nothing of where it came from. */
    case Unsaid = 'unsaid';

    /** The catalogue key a screen says it with. */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::Operator => 'plugins.settings.secret_from.operator',
            self::Recipe => 'plugins.settings.secret_from.recipe',
            self::Granted => 'plugins.settings.secret_from.granted',
            self::Minted => 'plugins.settings.secret_from.minted',
            self::Unsaid => 'plugins.settings.secret_from.unsaid',
        };
    }
}
