<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A written support bundle's file, fetched, or the reason it was not.
 *
 * What {@see AskingForHelp::fetch()} answers. A value rather than a raise,
 * which is `C1`: a stack that went to sleep between writing the bundle and
 * being asked for it is an ordinary state of the world, and the operator is
 * told which obstacle it was. The contents already drawn stand either way.
 */
final readonly class ABundleFetched
{
    private function __construct(private ABundleFile|Obstacle $answer) {}

    /** The file arrived, and this is it. */
    public static function as(ABundleFile $file): self
    {
        return new self($file);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TFetched of object
     * @template TMet of object
     *
     * @param Closure(ABundleFile): TFetched $fetched
     * @param Closure(Obstacle): TMet        $met
     *
     * @return TFetched|TMet
     */
    public function either(Closure $fetched, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $fetched($this->answer);
    }
}
