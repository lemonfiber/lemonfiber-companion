<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the stack answered asking to end a grant: what ending it would come to or came to, and every credential and grant as they stand after.
 */
final readonly class WhatARevokeCameTo
{
    private function __construct(private ACredentialRevoke $revoke, private TheCredentialsHeld $held) {}

    /** That revoke, with the credentials and grants the answer carried. */
    public static function of(ACredentialRevoke $revoke, TheCredentialsHeld $held): self
    {
        return new self($revoke, $held);
    }

    /** What ending the grant would come to, or came to. */
    public function revoke(): ACredentialRevoke
    {
        return $this->revoke;
    }

    /** Every credential and grant, as they stand after. */
    public function held(): TheCredentialsHeld
    {
        return $this->held;
    }
}
