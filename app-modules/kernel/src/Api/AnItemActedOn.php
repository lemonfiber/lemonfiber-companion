<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One download a healing run acted on, or would have: why, and each act in the order it was taken.
 *
 * Iterating it walks the acts in that order.
 *
 * @implements IteratorAggregate<int, AnActTaken>
 */
final readonly class AnItemActedOn implements IteratorAggregate
{
    /** @param list<AnActTaken> $acts */
    private function __construct(private ADownloadInTheQueue $download, private string $why, private array $acts) {}

    /** This download, acted on for this reason in the stack's words, with these acts in order. */
    public static function because(ADownloadInTheQueue $download, string $why, AnActTaken ...$acts): self
    {
        return new self($download, $why, array_values($acts));
    }

    /** The download. */
    public function download(): ADownloadInTheQueue
    {
        return $this->download;
    }

    /** Why it was acted on, in the stack's words. */
    public function why(): string
    {
        return $this->why;
    }

    /** @return Traversable<int, AnActTaken> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->acts);
    }
}
