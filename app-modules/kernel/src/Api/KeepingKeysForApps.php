<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Listing a stack's integration keys, and revoking one by name.
 *
 * Neither reads or carries a secret: the listing has none, and minting is not
 * offered here. Each takes a stack and a session, for the reason {@see Asking} gives.
 */
interface KeepingKeysForApps
{
    /** Ask a stack which keys it lists, or come away with a reason. */
    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfTheKeys;

    /** Revoke the key of that name, coming away with the listing as it stands after, or a reason. */
    public function revokedOn(Stack $stack, Session $session, AKeysName $name): WhatWasFoundOfTheKeys;
}
