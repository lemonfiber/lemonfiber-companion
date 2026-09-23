<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * Where one of lemonfiber's requests goes, as this machine is configured.
 *
 * **Nowhere is an answer, and it is not *switched off*.** A request can be
 * allowed with nowhere configured to reach, or switched off with somewhere
 * still set; an empty set here says only the first half, and whether it may go
 * out is {@see WhetherItIsAllowed}'s to say.
 *
 * Each destination is required to say something: a blank among real ones is a
 * place the list admits exists and will not name.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhereItGoes implements Countable, IteratorAggregate
{
    /** @param list<string> $destinations */
    private function __construct(private array $destinations) {}

    /**
     * These destinations, in the stack's order; none at all is nowhere.
     *
     * Reindexed, because a spread of named arguments keeps its string keys.
     */
    public static function to(string ...$destinations): self
    {
        foreach ($destinations as $destination) {
            if (trim($destination) === '') {
                throw RequestSaysNothing::about('destination');
            }
        }

        return new self(array_values($destinations));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->destinations);
    }

    public function count(): int
    {
        return count($this->destinations);
    }
}
