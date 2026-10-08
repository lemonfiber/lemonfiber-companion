<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;

use function in_array;

use IteratorAggregate;
use Traversable;

use function trim;

/**
 * Lines a stack said about a plugin, each a word or a sentence of its own: a proof's faults, the services claiming an ask, the checks an install broke, the approvals a recipe asks for.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class PluginLines implements Countable, IteratorAggregate
{
    /** @param list<string> $lines */
    private function __construct(private array $lines) {}

    /** These, in the stack's order, under the field they arrived in; a blank line is refused. */
    public static function under(string $field, string ...$lines): self
    {
        foreach ($lines as $line) {
            if (trim($line) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self(array_values($lines));
    }

    /** No lines at all. */
    public static function none(): self
    {
        return new self([]);
    }

    /** Those of these that are among the others as well, each once, in this order. */
    public function alsoIn(self $others): self
    {
        $kept = [];

        foreach ($this->lines as $line) {
            if (in_array($line, $others->lines, strict: true) && ! in_array($line, $kept, strict: true)) {
                $kept[] = $line;
            }
        }

        return new self($kept);
    }

    /** Whether there are none. */
    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }

    public function count(): int
    {
        return count($this->lines);
    }
}
