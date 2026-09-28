<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * What one wiring run came to: every connection it attempted, whether it could judge drift, and whether it only said so.
 *
 * **Whether drift could be judged is the run's own answer.** Where the record
 * of what lemonfiber last wrote could not be read, nothing this run reports
 * about drift was judged against it, and that is said rather than drawn as
 * sound.
 *
 * @implements IteratorAggregate<int, AConnection>
 */
final readonly class TheWiring implements Countable, IteratorAggregate
{
    /** @param list<AConnection> $connections */
    private function __construct(
        private HowDriftWasJudged $judged,
        private bool $rehearsed,
        private WhatIsUnsupported $unsupported,
        private array $connections,
    ) {}

    /** What a run that wrote reported; the connections are reindexed for a named spread's keys. */
    public static function written(HowDriftWasJudged $judged, WhatIsUnsupported $unsupported, AConnection ...$connections): self
    {
        return new self($judged, rehearsed: false, unsupported: $unsupported, connections: array_values($connections));
    }

    /** What a run that only said what it would do reported; reindexed the same way. */
    public static function rehearsed(HowDriftWasJudged $judged, WhatIsUnsupported $unsupported, AConnection ...$connections): self
    {
        return new self($judged, rehearsed: true, unsupported: $unsupported, connections: array_values($connections));
    }

    /** Whether drift could be judged against the record of what lemonfiber last wrote. */
    public function judged(): HowDriftWasJudged
    {
        return $this->judged;
    }

    /** Whether this run only said what it would do. */
    public function wasRehearsed(): bool
    {
        return $this->rehearsed;
    }

    /** The services this run could not wire because it cannot speak to them, each with why. */
    public function unsupported(): WhatIsUnsupported
    {
        return $this->unsupported;
    }

    /** @return Traversable<int, AConnection> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->connections);
    }

    public function count(): int
    {
        return count($this->connections);
    }
}
