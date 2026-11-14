<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/** How far a back-end is proven: never delivered, proven at a moment, or held for a reason. */
final readonly class HowFarItIsProven
{
    private function __construct(private ?Instant $proven, private string $held) {}

    public static function unproven(): self
    {
        return new self(null, '');
    }

    public static function provenAt(Instant $at): self
    {
        return new self($at, '');
    }

    public static function held(string $why): self
    {
        if (trim($why) === '') {
            throw WhereAlertsGoSaysNothing::about('why a back-end is held');
        }

        return new self(null, $why);
    }

    /**
     * @template TUnproven of object
     * @template TProven of object
     * @template THeld of object
     *
     * @param  Closure(): TUnproven       $unproven
     * @param  Closure(Instant): TProven  $proven
     * @param  Closure(string): THeld     $held
     * @return TUnproven|TProven|THeld
     */
    public function either(Closure $unproven, Closure $proven, Closure $held): object
    {
        if ($this->proven instanceof Instant) {
            return $proven($this->proven);
        }

        return $this->held === '' ? $unproven() : $held($this->held);
    }
}
