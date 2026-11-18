<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The token a member's search vouched for one title with, as the core signed it, for their ask to hand back, or none where the core signed none.
 */
final readonly class WhatTheSearchVouched
{
    private function __construct(private string $token) {}

    /** The token as the search gave it, or none where it is blank. */
    public static function given(string $token): self
    {
        return new self(trim($token) === '' ? '' : $token);
    }

    /** No token: the core signed none, or nothing carried one here. */
    public static function nothing(): self
    {
        return new self('');
    }

    /** Whether the search vouched for the title at all. */
    public function vouches(): bool
    {
        return $this->token !== '';
    }

    /** The token to hand back with the ask, empty where the search vouched for nothing. */
    public function token(): string
    {
        return $this->token;
    }
}
