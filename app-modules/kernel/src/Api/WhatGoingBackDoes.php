<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What putting one change back does, in the stack's word for it.
 *
 * Eight, and the contract closes the set, held to the wire by
 * `EveryWireValueIsACaseTest`. A word this app has no case for is refused at
 * the reading rather than drawn as the nearest one, for
 * {@see HowFarItGoesBack}'s reason.
 */
enum WhatGoingBackDoes: string
{
    /** A resource the change created is removed. */
    case Remove = 'remove';

    /** A setting is put back to what it held, or removed where it held nothing. */
    case Restore = 'restore';

    /** A path the change created is removed. */
    case Delete = 'delete';

    /** A region lemonfiber wrote into a file is taken back out. */
    case Withdraw = 'withdraw';

    /** A service is pinned back to the version it was standing on. */
    case Repin = 'repin';

    /** One field of a service's own resource is put back. */
    case Reconfigure = 'reconfigure';

    /** A key the change minted is revoked. */
    case Revoke = 'revoke';

    /** A key the change revoked is made good again. */
    case Reinstate = 'reinstate';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.run_back.does.%s', $this->value);
    }
}
