<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_find;
use function array_values;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * Every back-end a machine's alerts go to, in the core's order.
 *
 * @implements IteratorAggregate<int, AnAlertBackEnd>
 */
final readonly class AlertBackEnds implements IteratorAggregate
{
    /** @param list<AnAlertBackEnd> $backEnds */
    private function __construct(private array $backEnds) {}

    public static function these(AnAlertBackEnd ...$backEnds): self
    {
        return new self(array_values($backEnds));
    }

    public function isEmpty(): bool
    {
        return $this->backEnds === [];
    }

    /**
     * One back-end by its id, or that none is listed by it.
     *
     * @template TFound of object
     * @template TMissing of object
     *
     * @param  Closure(AnAlertBackEnd): TFound $found
     * @param  Closure(): TMissing             $missing
     * @return TFound|TMissing
     */
    public function one(AlertBackEndId $id, Closure $found, Closure $missing): object
    {
        $backEnd = array_find($this->backEnds, static fn(AnAlertBackEnd $one): bool => $one->id()->isTheSameAs($id));

        return $backEnd instanceof AnAlertBackEnd ? $found($backEnd) : $missing();
    }

    /** @return Traversable<int, AnAlertBackEnd> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->backEnds);
    }
}
