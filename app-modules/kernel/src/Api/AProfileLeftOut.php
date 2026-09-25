<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One profile a start would leave out, and what it would have needed. */
final readonly class AProfileLeftOut
{
    private function __construct(
        private string $profile,
        private WhatItWouldNeed $needs,
    ) {}

    /** A profile named as the stack names it; a blank one is refused. */
    public static function needing(string $profile, WhatItWouldNeed $needs): self
    {
        if (trim($profile) === '') {
            throw TheRehearsalSaysNothing::about('profile');
        }

        return new self($profile, $needs);
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function needs(): WhatItWouldNeed
    {
        return $this->needs;
    }
}
