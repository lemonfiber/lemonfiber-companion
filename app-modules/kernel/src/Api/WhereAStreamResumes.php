<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where a stream opened for a stack starts: after an event heard before, or from the start.
 *
 * From the start, a stack hands back no past alert.
 */
final readonly class WhereAStreamResumes
{
    private function __construct(private ?AnEventHeard $after) {}

    public static function after(AnEventHeard $event): self
    {
        return new self($event);
    }

    public static function fromTheStart(): self
    {
        return new self(null);
    }

    /**
     * Say what happens after an event and from the start, and get back what you built.
     *
     * @template TAfter of object
     * @template TFromTheStart of object
     *
     * @param Closure(AnEventHeard): TAfter  $after
     * @param Closure(): TFromTheStart       $fromTheStart
     *
     * @return TAfter|TFromTheStart
     */
    public function either(Closure $after, Closure $fromTheStart): object
    {
        return $this->after instanceof AnEventHeard ? $after($this->after) : $fromTheStart();
    }
}
