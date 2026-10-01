<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use Closure;
use Modules\Connection\Api\TheLock;
use Modules\Operator\Internal\Screens\Locked;

/**
 * Every screen the navigation stack builds, or the lock while it stands.
 *
 * The one place the lock is kept for navigation: a cold start, a link from
 * outside, a step back and any navigation at all build the lock screen in place
 * of the screen asked for until the lock opens, and nothing of that screen is
 * built, mounted or asked to read anything first. {@see TheLock} waives it
 * where the store holds nothing for it to guard.
 *
 * The navigation stack keeps the path it was asked for beside what this built,
 * which is how the lock screen goes on to it once the lock opens.
 */
final readonly class BehindTheLock
{
    /**
     * @param Closure(string): mixed $make how a screen is made, given its name
     *
     * @param-later-invoked-callable $make
     */
    public function __construct(private TheLock $lock, private Closure $make) {}

    /** The screen asked for, or the lock. */
    public function screen(string $asked): mixed
    {
        return ($this->make)($this->lock->standing()->either(
            held: static fn(): ScreenToBuild => ScreenToBuild::named(Locked::class),
            open: static fn(): ScreenToBuild => ScreenToBuild::named($asked),
        )->class);
    }
}
