<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * Changes of one kind in a release's notes, under the name the notes give them.
 *
 * The title and every entry are the stack's own words, drawn as they came, and
 * the entries are what iterating it gives, in the order the notes list them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class AGroupOfChanges implements IteratorAggregate
{
    /** @param list<string> $entries */
    private function __construct(private string $title, private array $entries) {}

    /**
     * The group the notes named, and what they list under it.
     *
     * A blank title is refused rather than drawn: a heading with nothing in it
     * reads as a screen that failed to load one.
     */
    public static function titled(string $title, string ...$entries): self
    {
        if (trim($title) === '') {
            throw VersionIsBlank::inAGroupOfChanges();
        }

        return new self($title, array_values($entries));
    }

    public function title(): string
    {
        return $this->title;
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->entries);
    }
}
