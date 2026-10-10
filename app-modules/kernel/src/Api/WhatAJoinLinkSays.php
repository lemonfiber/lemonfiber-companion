<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The parameters a join link carries: the one place each is spelled.
 */
enum WhatAJoinLinkSays: string
{
    case Address = 'address';

    case Fingerprint = 'fingerprint';

    case Stack = 'stack';

    case Expires = 'expires';

    case Name = 'name';

    case Claim = 'claim';

    /** Whether a link carries it however the invitation stands, which every one but the claim does. */
    public function isAlwaysCarried(): bool
    {
        return $this !== self::Claim;
    }
}
