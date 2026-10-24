<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Ending one grant of a credential to a third-party plugin.
 *
 * One act, sent as `credentials-revoke`: read first with no offer, then sent
 * again with the offer the reading named.
 */
enum RevokingAGrant: string implements AnAction
{
    case Revoke = 'credentials-revoke';

    public function asked(): string
    {
        return $this->value;
    }
}
