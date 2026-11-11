<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A moment the stack names, or the fact that it named none.
 *
 * Two arms because a stack has two answers, and a moment it would not name is
 * never drawn as one it did.
 */
final readonly class WhenItSays
{
    private function __construct(private ?Instant $at) {}

    /** The stack named this moment. */
    public static function at(Instant $at): self
    {
        return new self($at);
    }

    /** The stack named no moment. */
    public static function unsaid(): self
    {
        return new self(null);
    }

    /** Whether the stack named a moment. */
    public function isSaid(): bool
    {
        return $this->at instanceof Instant;
    }

    /**
     * Say what happens for a named moment and for none.
     *
     * @template TAt of object
     * @template TUnsaid of object
     *
     * @param  Closure(Instant): TAt  $at
     * @param  Closure(): TUnsaid     $unsaid
     * @return TAt|TUnsaid
     */
    public function either(Closure $at, Closure $unsaid): object
    {
        return $this->at instanceof Instant ? $at($this->at) : $unsaid();
    }
}
