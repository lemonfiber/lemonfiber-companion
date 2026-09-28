<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Taking lemonfiber off a machine, before or after it happened: the reading, and whether anything was removed.
 *
 * Kept as two halves because they answer two questions. How much of the
 * reading was read is about the reading, before anything goes; whether the
 * removal finished is about the removal, after. A complete reading can end in
 * a partial removal and an incomplete one can end complete.
 */
final readonly class AnUninstall
{
    private function __construct(private WhatTakingItOffComesTo $manifest, private WhereTakingItOffGot $removal) {}

    /** The reading, and where the removal got. */
    public static function of(WhatTakingItOffComesTo $manifest, WhereTakingItOffGot $removal): self
    {
        return new self($manifest, $removal);
    }

    /** What removing would come to, or came to. */
    public function manifest(): WhatTakingItOffComesTo
    {
        return $this->manifest;
    }

    /** Whether anything was removed on this run, and what became of it. */
    public function removal(): WhereTakingItOffGot
    {
        return $this->removal;
    }
}
