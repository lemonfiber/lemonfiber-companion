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
 * Sentences the stack says about a reading, in its order: what to know before
 * trusting it, or what falls outside it.
 *
 * Each is the stack's own wording and none may be blank — a blank among real
 * ones is a caution the list admits and will not say.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class Remarks implements Countable, IteratorAggregate
{
    /** @param list<string> $said */
    private function __construct(private array $said) {}

    /** These sentences, in the stack's order; reindexed for a named spread's keys. */
    public static function of(string ...$said): self
    {
        foreach ($said as $one) {
            if (trim($one) === '') {
                throw LineSaysNothing::about('remark');
            }
        }

        return new self(array_values($said));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->said);
    }

    public function count(): int
    {
        return count($this->said);
    }
}
