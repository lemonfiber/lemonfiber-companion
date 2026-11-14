<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
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


    /** @return Traversable<int, AnAlertBackEnd> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->backEnds);
    }
}
