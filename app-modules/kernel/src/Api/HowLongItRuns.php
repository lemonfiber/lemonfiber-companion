<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** How long a title or an episode runs, in whole minutes, where the core knows. */
final readonly class HowLongItRuns
{
    private function __construct(private ?int $minutes) {}

    public static function minutes(int $minutes): self
    {
        return new self($minutes);
    }

    public static function unstated(): self
    {
        return new self(null);
    }

    /**
     * @template TStated of object
     * @template TUnstated of object
     *
     * @param Closure(int): TStated  $minutes
     * @param Closure(): TUnstated   $unstated
     *
     * @return TStated|TUnstated
     */
    public function either(Closure $minutes, Closure $unstated): object
    {
        return $this->minutes === null ? $unstated() : $minutes($this->minutes);
    }
}
