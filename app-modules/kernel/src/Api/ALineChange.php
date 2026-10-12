<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * What the operator asks a stack's line to become: each part written, and a lift where one is asked for.
 *
 * Every part is carried as the operator wrote it: what `50%` or `07:00-23:00`
 * means is read in the core, which refuses what it cannot read. A part not
 * written stays as it is.
 *
 * @implements IteratorAggregate<int, APartWritten>
 */
final readonly class ALineChange implements IteratorAggregate
{
    /** @param list<APartWritten> $written */
    private function __construct(private array $written, private ALift $lift) {}

    /** A change asking at least one thing, each part blank where it stays as it is, refused where it asks nothing at all. */
    public static function asked(
        ALift $lift,
        string $down = '',
        string $up = '',
        string $active = '',
        string $line = '',
        string $cap = '',
        string $exceeded = '',
    ): self {
        $written = [];

        foreach ([[APartOfTheLine::Down, $down], [APartOfTheLine::Up, $up], [APartOfTheLine::Active, $active], [APartOfTheLine::Line, $line], [APartOfTheLine::Cap, $cap], [APartOfTheLine::Exceeded, $exceeded]] as [$part, $text]) {
            if (trim($text) !== '') {
                $written[] = APartWritten::of($part, $text);
            }
        }

        if ($written === [] && $lift->isNone()) {
            throw LineChangeAsksNothing::atAll();
        }

        return new self($written, $lift);
    }

    /** @return Traversable<int, APartWritten> each part written, in the order the line is read */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->written);
    }

    /** The lift asked for, or none. */
    public function lift(): ALift
    {
        return $this->lift;
    }
}
