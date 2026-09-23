<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether an event set apart from the preset is heard about.
 *
 * Two cases rather than a boolean, so a row says which in words at every call
 * site. Both are exceptions to the preset — an event set apart to be heard
 * and one set apart to be silenced are equally the operator's decision.
 */
enum WhetherItIsHeard: string
{
    /** Heard about, whatever the preset would say. */
    case Heard = 'heard';

    /** Kept quiet, whatever the preset would say. */
    case Silenced = 'silenced';

    /** Read off the wire's boolean, which is the only place one is taken. */
    public static function said(bool $wanted): self
    {
        return $wanted ? self::Heard : self::Silenced;
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.alerts.heard.%s', $this->value);
    }
}
