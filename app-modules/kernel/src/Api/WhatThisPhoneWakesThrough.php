<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What this phone could be woken through: the distributors installed, or nothing where the phone cannot be woken this way at all. */
final readonly class WhatThisPhoneWakesThrough
{
    private function __construct(private ?TheDistributors $installed) {}

    /** These are installed, which may be none. */
    public static function installed(TheDistributors $installed): self
    {
        return new self($installed);
    }

    /** This phone cannot be woken through a distributor: an iPhone, or an Android too old to keep the key. */
    public static function notHere(): self
    {
        return new self(null);
    }

    /**
     * @template TInstalled of object
     * @template TNotHere of object
     *
     * @param  Closure(TheDistributors): TInstalled $installed
     * @param  Closure(): TNotHere                  $notHere
     * @return TInstalled|TNotHere
     */
    public function either(Closure $installed, Closure $notHere): object
    {
        return $this->installed instanceof TheDistributors ? $installed($this->installed) : $notHere();
    }
}
