<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Something this app can ask a stack to do, named as the stack's surface names it.
 *
 * What a button offers, and so what a screen asks about before drawing one:
 * whether the stack it is about declares the action, which
 * {@see KnowingWhatAStackOffers} answers. The name is spelled once, by the
 * case or the value that carries it, and never at a call site.
 */
interface AnAction
{
    /** The action's name, which is the last part of the path the stack serves it at. */
    public function asked(): string;
}
