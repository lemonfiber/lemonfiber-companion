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
 * Every device the media server lists as signed in to the account now.
 *
 * @implements IteratorAggregate<int, ASignedInDevice>
 */
final readonly class TheSignedInDevices implements Countable, IteratorAggregate
{
    /** @param list<ASignedInDevice> $each */
    private function __construct(private array $each) {}

    /** These, in the stack's order. */
    public static function of(ASignedInDevice ...$each): self
    {
        return new self(array_values($each));
    }

    /** @return Traversable<int, ASignedInDevice> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->each);
    }

    public function count(): int
    {
        return count($this->each);
    }
}
