<?php

declare(strict_types=1);

namespace Modules\Design\View;

/**
 * How loudly an action is drawn.
 *
 * `primary` is the accent pair, and a screen draws one way forward in it.
 * `tonal` is every other act a screen offers as a button: the line colour
 * with the text role on it.
 */
enum Prominence: string
{
    case Primary = 'primary';
    case Tonal = 'tonal';

    /** The platform button's variant that draws it. */
    public function variant(): string
    {
        return match ($this) {
            self::Primary => 'primary',
            self::Tonal => 'secondary',
        };
    }
}
