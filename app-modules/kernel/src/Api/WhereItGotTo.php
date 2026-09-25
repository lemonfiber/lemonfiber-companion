<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where one item got to: the answer to *where is my show?*.
 *
 * Nothing asked for is an answer of its own — no monitored item matched what
 * was followed — and is told apart from an item that was followed and got
 * nowhere. A trace carries how sure it is of the item it followed, since a
 * guess drawn as fact is worse than a marked one.
 */
final readonly class WhereItGotTo
{
    private function __construct(
        private string $item,
        private ?WhatTheTraceFound $found,
    ) {}

    /** No monitored item matched what was followed: nobody asked for it. */
    public static function nothingAskedFor(string $item): self
    {
        return new self(self::named($item), null);
    }

    /** An item was followed, and this is what was found. */
    public static function followed(string $item, WhatTheTraceFound $found): self
    {
        return new self(self::named($item), $found);
    }

    /** What was followed, as it was asked for. */
    public function item(): string
    {
        return $this->item;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TNothing of object
     * @template TFollowed of object
     *
     * @param Closure(): TNothing $nothingAskedFor
     * @param Closure(WhatTheTraceFound): TFollowed $followed
     *
     * @return TNothing|TFollowed
     */
    public function either(Closure $nothingAskedFor, Closure $followed): object
    {
        $found = $this->found;

        return $found instanceof WhatTheTraceFound ? $followed($found) : $nothingAskedFor();
    }

    private static function named(string $item): string
    {
        if (trim($item) === '') {
            throw TheTraceSaysNothing::about('item');
        }

        return $item;
    }
}
