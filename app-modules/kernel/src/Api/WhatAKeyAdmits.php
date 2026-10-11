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
    private function __construct(private ?bool $acts, private string $member) {}

    /** Every served read and the event stream. */
    public static function reading(): self
    {
        return new self(acts: false, member: '');
    }

    /** What reading admits, and the actions the contract lists as callable by a key. */
    public static function acting(): self
    {
        return new self(acts: true, member: '');
    }

    /** What that household member's own session admits; a blank member is refused. */
    public static function aMembersOwn(string $member): self
    {
        if (trim($member) === '') {
            throw KeySaysNothing::about('member');
        }

        return new self(acts: null, member: $member);
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
        return match ($this->acts) {
            false => $reading(),
            true => $acting(),
            null => $member($this->member),
        };
    }
}
