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
 * Names a removal reports by the name its reading gave them: what went, or the credentials it destroyed.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class NamedOnTheManifest implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** These, in the stack's order, under the field they arrived in; a blank name is refused. */
    public static function under(string $field, string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw UninstallSaysNothing::about($field);
            }
        }

        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }

    public function count(): int
    {
        return count($this->names);
    }
}
