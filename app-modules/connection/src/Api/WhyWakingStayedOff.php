<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhyNoEndpoint;

/**
 * Why waking a stack was not turned on, and nothing of it was left behind.
 *
 * This phone has no key on the stack to name the endpoint after, the
 * distributor gave no endpoint, the stack refused it in its words or could not
 * be reached, or the phone could not keep that it is on.
 */
final readonly class WhyWakingStayedOff
{
    private function __construct(private WhyNoEndpoint|ARefusalInItsWords|Obstacle|null $why, private bool $named) {}

    /** This phone holds no key on the stack, so there is no name to register under. */
    public static function noKeyName(): self
    {
        return new self(null, named: false);
    }

    public static function noEndpoint(WhyNoEndpoint $why): self
    {
        return new self($why, named: true);
    }

    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why, named: true);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why, named: true);
    }

    /** The stack keeps the endpoint and this phone could not keep that it does, so the stack was asked to forget it. */
    public static function notKept(): self
    {
        return new self(null, named: true);
    }

    /**
     * @template TNoKeyName of object
     * @template TNoEndpoint of object
     * @template TRefused of object
     * @template TMet of object
     * @template TNotKept of object
     *
     * @param  Closure(): TNoKeyName                  $noKeyName
     * @param  Closure(WhyNoEndpoint): TNoEndpoint    $noEndpoint
     * @param  Closure(ARefusalInItsWords): TRefused  $refused
     * @param  Closure(Obstacle): TMet                $met
     * @param  Closure(): TNotKept                    $notKept
     * @return TNoKeyName|TNoEndpoint|TRefused|TMet|TNotKept
     */
    public function either(Closure $noKeyName, Closure $noEndpoint, Closure $refused, Closure $met, Closure $notKept): object
    {
        return match (true) {
            $this->why instanceof WhyNoEndpoint => $noEndpoint($this->why),
            $this->why instanceof ARefusalInItsWords => $refused($this->why),
            $this->why instanceof Obstacle => $met($this->why),
            $this->named => $notKept(),
            default => $noKeyName(),
        };
    }
}
