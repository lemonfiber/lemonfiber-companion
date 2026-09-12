<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_slice;
use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What to do about a problem, most likely first.
 *
 * A typed collection rather than an array, which is what D1 asks for and what
 * makes the order part of the type's promise: the server sorts these by
 * likelihood, and a screen that re-sorts them is discarding the one thing it
 * cannot work out for itself.
 *
 * Empty is a legitimate value and not an error. A problem with no known remedy
 * is what `Standing::Unknown` is for, and an empty collection says it without
 * a null anywhere (C2).
 *
 * @implements IteratorAggregate<int, Remedy>
 */
final readonly class Remedies implements IteratorAggregate
{
    /** @param array<int, Remedy> $remedies */
    private function __construct(private array $remedies) {}

    public static function of(Remedy ...$remedies): self
    {
        // Not habit: a variadic collected from *named* arguments has string
        // keys, so `Remedies::of(likeliest: $one)` would otherwise build a
        // collection that is not a list — and `likeliest()` slices by position.
        return new self(array_values($remedies));
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * The first thing to try, or nothing where there is none.
     *
     * A collection rather than a nullable `Remedy`: "the most likely one" and
     * "there are none" are both answers a screen acts on, and a null would
     * make the second one indistinguishable from a list nobody read (C2).
     */
    public function likeliest(): self
    {
        return new self(array_slice($this->remedies, 0, 1));
    }

    public function count(): int
    {
        return count($this->remedies);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->remedies);
    }
}
