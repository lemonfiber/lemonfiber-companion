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
 * The downloads the stack last said are moving, each by what is being downloaded.
 *
 * Empty where none is moving, and where the stack could not say: either way
 * there is nothing to name, and nothing is said about a download nobody
 * reported.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class DownloadsMoving implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** These, in the stack's order; a blank name is refused. */
    public static function these(string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw DownloadIsUnnamed::amongThoseMoving();
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
