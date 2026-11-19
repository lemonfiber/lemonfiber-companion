<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How long the stack said to wait before trying again, or that it did not say.
 */
final readonly class WhenToTryAgain
{
    private function __construct(private bool $said, private HowLong $wait) {}

    /** The stack named no wait. */
    public static function unsaid(): self
    {
        return new self(said: false, wait: HowLong::ofSeconds(0));
    }

    /** The stack said to wait this long. */
    public static function after(HowLong $wait): self
    {
        return new self(said: true, wait: $wait);
    }

    /** Whether the stack named a wait. */
    public function isSaid(): bool
    {
        return $this->said;
    }

    /** The wait it named, none where it named none. */
    public function wait(): HowLong
    {
        return $this->wait;
    }
}
