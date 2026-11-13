<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What asking which ways a member is told on came to: the ways the core answered with, or what stood in the way. */
final readonly class HowBeingToldCameBack
{
    private function __construct(private WaysToBeTold $ways, private ?Obstacle $why) {}

    public static function told(WaysToBeTold $ways): self
    {
        return new self($ways, null);
    }

    public static function refused(Obstacle $why): self
    {
        return new self(WaysToBeTold::offered(phone: false, ways: []), $why);
    }

    /**
     * @template TTold of object
     * @template TRefused of object
     *
     * @param  Closure(WaysToBeTold): TTold  $told
     * @param  Closure(Obstacle): TRefused   $refused
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        return $this->why instanceof Obstacle ? $refused($this->why) : $told($this->ways);
    }
}
