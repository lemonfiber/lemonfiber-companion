<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What putting one change back does, in the stack's word for it.
 *
 * The contract closes the set, held to the wire by
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

    /** A file lemonfiber wrote over is written back to what it held before. */
    case Rewind = 'rewind';

    /** A service's state is moved back to where it was, from where it was moved to. */
    case MoveBack = 'move-back';

    /** A value in the environment file is moved back to the key it was under. */
    case RenameBack = 'rename-back';

    /** A copy of a volume is kept where it is, the volume itself never having changed. */
    case KeepCopy = 'keep-copy';

    /** A service is pinned back to the version it was standing on. */
    case Repin = 'repin';

    /** One field of a service's own resource is put back. */
    case Reconfigure = 'reconfigure';

    /** A key the change minted is revoked. */
    case Revoke = 'revoke';

    /** A key the change revoked is made good again. */
    case Reinstate = 'reinstate';

    /** A device key the change registered is revoked. */
    case RevokeDevice = 'revoke-device';

    /** A device key the change revoked is made good again. */
    case ReinstateDevice = 'reinstate-device';

    /** Put a setting in the settings store back to what it held. */
    case Resettle = 'resettle';

    /** Put a secret in the secret store back to the value it held. */
    case Rehold = 'rehold';

    /** A grant of a credential to a plugin is given back, or the one the change gave is ended. */
    case Regrant = 'regrant';

    /** The media server's internal certificate is left as it is; only replacing it again puts it back. */
    case Unreplaced = 'unreplaced';

    /** A service is started again, or stopped again. */
    case Run = 'run';

    /** The operator's own overlay is left as it stands, beyond what lemonfiber puts back. */
    case Leave = 'leave';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.run_back.does.%s', $this->value);
    }
}
