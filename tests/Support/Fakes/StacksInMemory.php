<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

/**
 * The configured stacks, held for as long as a test runs.
 *
 * What every test that needs a paired device will reach for, which is why the
 * contract suite drives this and {@see \Modules\Vault\Api\PlatformStacks}
 * through the same expectations: a fake that is easier to satisfy than the
 * platform is a fake that quietly widens what the rest of the suite is written
 * against.
 *
 * Written by hand rather than mocked — `G1` — so a change to the port fails to
 * compile here rather than drifting.
 */
final class StacksInMemory implements Stacks
{
    private Configured $held;

    private function __construct(private readonly ?WhyAStackCannotBeRemembered $refusing)
    {
        $this->held = Configured::none();
    }

    /** A device that can remember a stack, and has not been asked to yet. */
    public static function working(): self
    {
        return new self(null);
    }

    /** A device that cannot write one down, and says which nothing stopped it. */
    public static function refusing(WhyAStackCannotBeRemembered $why): self
    {
        return new self($why);
    }

    public function configured(): Configured
    {
        return $this->held;
    }

    public function remember(Stack $stack): Remembered
    {
        if ($this->refusing instanceof WhyAStackCannotBeRemembered) {
            return Remembered::refused($this->refusing);
        }

        $this->held = $this->held->with($stack);

        return Remembered::safely();
    }
}
