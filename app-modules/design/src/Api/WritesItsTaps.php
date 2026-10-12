<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * A screen or a component whose template hands a control a tap that carries a value.
 *
 * The template binds it, `:tap="$this->tapping('choose', $form->name)"`, and
 * never writes the value into the expression itself.
 */
trait WritesItsTaps
{
    /** The tap calling `$method` with `$with`, each value read back by the screen as it is. */
    public function tapping(string $method, int|string ...$with): string
    {
        return ATap::calling($method, ...$with)->written();
    }
}
