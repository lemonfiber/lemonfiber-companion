<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Listing the phone keys a stack has registered to prove a mint, and revoking one by name.
 *
 * Only public halves are the stack's, and neither reads any: the listing names each key and where it lives.
 * Each takes a stack and a session, for the reason {@see Asking} gives.
 */
interface KeepingPhoneKeys
{
    /** Ask a stack which phone keys it lists, or come away with a reason. */
    public function listedOn(Stack $stack, Session $session): WhatWasFoundOfThePhoneKeys;

    /** Revoke the phone key of that name, coming away with the listing as it stands after, its refusal, or a reason. */
    public function revokedOn(Stack $stack, Session $session, APhoneKeysName $name): WhatWasFoundOfThePhoneKeys;
}
