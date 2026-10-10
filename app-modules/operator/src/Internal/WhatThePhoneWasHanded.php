<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\Pairing;

/**
 * What a phone somebody was invited on was handed: an invitation's join link, the code for a new phone, or neither.
 */
final readonly class WhatThePhoneWasHanded
{
    private function __construct(
        private ?AJoinLink $link,
        private ?Pairing $code,
        private WhatFindingTheHouseMet $met,
    ) {}

    /** An invitation's join link. */
    public static function aLink(AJoinLink $link): self
    {
        return new self($link, null, WhatFindingTheHouseMet::LinkUnusable);
    }

    /** The code whoever runs the house shows for a new phone. */
    public static function aCode(Pairing $code): self
    {
        return new self(null, $code, WhatFindingTheHouseMet::CodeUnreadable);
    }

    /** Neither, for the reason given. */
    public static function nothingUsable(WhatFindingTheHouseMet $met): self
    {
        return new self(null, null, $met);
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template T
     *
     * @param Closure(AJoinLink): T              $link
     * @param Closure(Pairing): T                $code
     * @param Closure(WhatFindingTheHouseMet): T $refused
     *
     * @return T
     */
    public function either(Closure $link, Closure $code, Closure $refused): mixed
    {
        if ($this->link instanceof AJoinLink) {
            return $link($this->link);
        }

        if ($this->code instanceof Pairing) {
            return $code($this->code);
        }

        return $refused($this->met);
    }
}
