<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How much of a traced item is here: a whole item, or a series counted season by season.
 *
 * A film is the whole item and has no parts. A series is imported the moment
 * one episode lands, which reads as done while the rest are missing, so its
 * parts are counted.
 */
final readonly class HowMuchOfItIsHere
{
    private function __construct(private ?ASeriesCounted $series) {}

    /** A whole item, with no parts to count. */
    public static function aWholeItem(): self
    {
        return new self(null);
    }

    /** A series, counted. */
    public static function inParts(ASeriesCounted $series): self
    {
        return new self($series);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TWhole of object
     * @template TParts of object
     *
     * @param Closure(): TWhole $whole
     * @param Closure(ASeriesCounted): TParts $inParts
     *
     * @return TWhole|TParts
     */
    public function either(Closure $whole, Closure $inParts): object
    {
        $series = $this->series;

        return $series instanceof ASeriesCounted ? $inParts($series) : $whole();
    }
}
