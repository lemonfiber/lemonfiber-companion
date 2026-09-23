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
 * Where every service in a stack comes from, in the order the stack declares them.
 *
 * Every service the stack holds rather than the ones it is running: what is
 * *in* this stack is the question, and an answer narrowed to what runs would
 * leave somebody unable to ask about the service they are deciding whether to
 * start.
 *
 * **The order is the stack's**, and nothing here re-sorts it. **Each service
 * appears once**, and a second origin for one is refused, because whichever a
 * screen drew, the other would be the one somebody checked.
 *
 * @implements IteratorAggregate<int, WhereItComesFrom>
 */
final readonly class WhereTheServicesComeFrom implements Countable, IteratorAggregate
{
    /** @param list<WhereItComesFrom> $origins */
    private function __construct(private array $origins) {}

    /**
     * The origins, in the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function declaring(WhereItComesFrom ...$origins): self
    {
        $seen = [];

        foreach ($origins as $origin) {
            foreach ($seen as $earlier) {
                if ($earlier->isTheSameAs($origin->service())) {
                    throw OriginSaysNothing::twice($origin->service());
                }
            }

            $seen[] = $origin->service();
        }

        return new self(array_values($origins));
    }

    /** @return Traversable<int, WhereItComesFrom> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->origins);
    }

    public function count(): int
    {
        return count($this->origins);
    }
}
