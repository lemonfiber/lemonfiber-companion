<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the stack answered asking to replace the internal certificate: what replacing it would do or came to, and every credential and grant as they stand after.
 */
final readonly class WhatAReplacementCameTo
{
    private function __construct(private ACertificateReplacement $replacement, private TheCredentialsHeld $held) {}

    /** That replacement, with the credentials and grants the answer carried. */
    public static function of(ACertificateReplacement $replacement, TheCredentialsHeld $held): self
    {
        return new self($replacement, $held);
    }

    /** What replacing it would do, or came to. */
    public function replacement(): ACertificateReplacement
    {
        return $this->replacement;
    }

    /** Every credential and grant, as they stand after. */
    public function held(): TheCredentialsHeld
    {
        return $this->held;
    }
}
