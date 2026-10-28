<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * One title's screen, as a template draws it.
 *
 * The answers every member reading has — signed out, stopped, told — and the
 * one a title adds: absent, which is how the core answers a title outside the
 * member's limits and one the household does not hold alike, and is signed in
 * with neither a title nor what stopped it.
 */
final readonly class WhatThisTitleTurnedOutToBe
{
    private function __construct(
        public bool $isSignedIn,
        public ?WhatTheTitleSays $title,
        public ?WhatStoppedAMember $stopped,
    ) {}

    /** The core answered with the title. */
    public static function told(WhatTheTitleSays $title): self
    {
        return new self(isSignedIn: true, title: $title, stopped: null);
    }

    /** The core says the title is not on this member's shelf. */
    public static function absent(): self
    {
        return new self(isSignedIn: true, title: null, stopped: null);
    }

    /** This device no longer holds a session for that stack. */
    public static function theSessionEnded(): self
    {
        return new self(isSignedIn: false, title: null, stopped: null);
    }

    /** Something stood in the way, and this is what the member met. */
    public static function somethingStopped(Obstacle $why): self
    {
        $stopped = WhatStoppedAMember::by($why);

        return $stopped->endsTheSession()
            ? self::theSessionEnded()
            : new self(isSignedIn: true, title: null, stopped: $stopped);
    }

    /** The screen, the title's poster drawn from `$source`; an answer with no title has no poster to draw. */
    public function pictured(?string $source): self
    {
        return $this->title instanceof WhatTheTitleSays
            ? new self($this->isSignedIn, $this->title->pictured($source), $this->stopped)
            : $this;
    }
}
