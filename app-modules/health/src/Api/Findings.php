<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What a diagnostic run found.
 *
 * A typed collection rather than an array, which is what `D1` asks for and
 * what makes the order part of the type's promise: the order a report arrives
 * in is the order the checks ran, and that is information — two findings in
 * the same category, one of which caused the other, read differently the other
 * way round.
 *
 * Empty is a legitimate value. A run that found nothing is the healthy case,
 * not a missing report, and an empty collection says it without a null
 * anywhere (C2).
 *
 * @implements IteratorAggregate<int, Finding>
 */
final readonly class Findings implements IteratorAggregate
{
    /** @param array<int, Finding> $findings */
    private function __construct(private array $findings) {}

    public static function of(Finding ...$findings): self
    {
        // Not habit: a variadic collected from named arguments has string
        // keys, and everything below reads this by position.
        return new self(array_values($findings));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->findings);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->findings);
    }
}
