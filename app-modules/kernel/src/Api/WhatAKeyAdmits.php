<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What one integration key admits: every read, the reads and the short list of actions, or one member's own.
 */
final readonly class WhatAKeyAdmits
{
    /** @param bool|string $admits whether it acts, or the member whose own it admits */
    private function __construct(private bool|string $admits) {}

    /** Every served read and the event stream. */
    public static function reading(): self
    {
        return new self(admits: false);
    }

    /** What reading admits, and the actions the contract lists as callable by a key. */
    public static function acting(): self
    {
        return new self(admits: true);
    }

    /** What that household member's own session admits; a blank member is refused. */
    public static function aMembersOwn(string $member): self
    {
        if (trim($member) === '') {
            throw KeySaysNothing::about('member');
        }

        return new self(admits: $member);
    }

    /**
     * Say what happens for each scope, and get back what you built.
     *
     * @template R of object
     *
     * @param Closure(): R       $reading
     * @param Closure(): R       $acting
     * @param Closure(string): R $member
     *
     * @return R
     */
    public function either(Closure $reading, Closure $acting, Closure $member): object
    {
        return match ($this->admits) {
            false => $reading(),
            true => $acting(),
            default => $member($this->admits),
        };
    }
}
