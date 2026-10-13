<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What removing everything lemonfiber keeps on a machine lists, and how far it went.
 */
final readonly class TheForgetting
{
    private function __construct(private WhatThisMachineKeeps $listed, private WhereForgettingGot $got) {}

    /** The stack's list of what it keeps, and how far removing it went. */
    public static function said(WhatThisMachineKeeps $listed, WhereForgettingGot $got): self
    {
        return new self($listed, $got);
    }

    /** What would go, or what was there, as the stack listed it. */
    public function listed(): WhatThisMachineKeeps
    {
        return $this->listed;
    }

    public function got(): WhereForgettingGot
    {
        return $this->got;
    }
}
