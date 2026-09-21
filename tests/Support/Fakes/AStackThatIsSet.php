<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\HowItIsSet;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Override;

/**
 * {@see Arranging}, answered from what a test put in it.
 *
 * The second implementation the port needs, and the one every screen test
 * reads through. Counts its askings and remembers which stack it was asked
 * about, because two of the things worth proving about this port are that the
 * screen asks the stack it is on and that it asks once rather than once per
 * row drawn.
 */
final class AStackThatIsSet implements Arranging
{
    private function __construct(
        private readonly Settings $set,
        private readonly ?Obstacle $why,
        private ?Stack $asked = null,
        private int $askings = 0,
    ) {}

    public static function to(Settings $set): self
    {
        return new self(set: $set, why: null);
    }

    /**
     * A stack that answered, holding nothing.
     *
     * Named apart from {@see to()} with an empty {@see Settings} so a test
     * reads as the case it is about. The two are the same object and the
     * difference is which sentence the test is making.
     */
    public static function toNothing(): self
    {
        return new self(set: Settings::none(), why: null);
    }

    public static function met(Obstacle $why): self
    {
        return new self(set: Settings::none(), why: $why);
    }

    public function askedAbout(): ?Stack
    {
        return $this->asked;
    }

    public function askings(): int
    {
        return $this->askings;
    }

    #[Override]
    public function asItStands(Stack $stack, Session $session): HowItIsSet
    {
        $this->asked = $stack;
        $this->askings++;

        return $this->why instanceof Obstacle
            ? HowItIsSet::refused($this->why)
            : HowItIsSet::told($this->set);
    }
}
