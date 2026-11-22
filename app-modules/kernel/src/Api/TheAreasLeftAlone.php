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
 * Every area the operator told the stack to leave alone, in the order declared, and how many more it left unlisted.
 *
 * @implements IteratorAggregate<int, AnAreaLeftAlone>
 */
final readonly class TheAreasLeftAlone implements Countable, IteratorAggregate
{
    use ReadsWhatIsLeftAlone;

    /** @param list<AnAreaLeftAlone> $areas */
    private function __construct(private array $areas, private int $more) {}

    public static function these(int $more, AnAreaLeftAlone ...$areas): self
    {
        return new self(array_values($areas), self::counted($more, 'declarations'));
    }

    public function more(): int
    {
        return $this->more;
    }

    public function count(): int
    {
        return count($this->areas);
    }

    /** @return Traversable<int, AnAreaLeftAlone> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->areas);
    }
}
