<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;

/**
 * Whether a device still holds a session for a stack.
 *
 * {@see \Modules\Kernel\Api\WhetherItIsHeld} answers in two closure arms rather
 * than with a nullable, which is `C2`'s cure and right — a caller cannot read
 * *no session* as *a session that is empty*. What a test wants back from it is a
 * yes or a no, so the arms are folded here once instead of in every case that
 * asks.
 *
 * Its own class rather than one declared inside a suite: a class written in a
 * test file is one nothing can autoload, so the dependency analyser reports it
 * as unknown and cannot check what it uses.
 */
final readonly class WhatTheKeychainStillHolds
{
    private function __construct(public bool $held) {}

    public static function forThe(SecureStorage $keychain, StackId $stack): self
    {
        return $keychain->resume($stack)->either(
            held: static fn(): self => new self(held: true),
            notHeld: static fn(): self => new self(held: false),
        );
    }
}
