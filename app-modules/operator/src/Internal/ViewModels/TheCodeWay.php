<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The way to sign in by a code, as a password sign-in offers it.
 */
final readonly class TheCodeWay
{
    /**
     * @param bool   $offered        whether this sign-in offers it at all: a member signing in to an account, never the operator or a member choosing a password
     * @param bool   $leads          whether it leads, because a code last let a member in on this house
     * @param bool   $besideARefusal whether a password was just refused, so the screen says some houses sign in with a code instead
     * @param string $goes           where it goes
     */
    public function __construct(
        public bool $offered,
        public bool $leads,
        public bool $besideARefusal,
        public string $goes,
    ) {}
}
