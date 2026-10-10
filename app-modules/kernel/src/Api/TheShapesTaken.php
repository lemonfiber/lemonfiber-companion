<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function in_array;

use IteratorAggregate;
use Traversable;

/**
 * Every service of a plugin taking a privileged shape, as an install's reading states them.
 *
 * @implements IteratorAggregate<int, AShapeTaken>
 */
final readonly class TheShapesTaken implements IteratorAggregate
{
    /** @param list<AShapeTaken> $taken */
    private function __construct(private array $taken) {}

    /** These, in the stack's order. */
    public static function these(AShapeTaken ...$taken): self
    {
        return new self(array_values($taken));
    }

    /** What approving each is written as, in the stack's order. */
    public function approvals(): PluginLines
    {
        $approvals = [];

        foreach ($this->taken as $taken) {
            $approvals[] = $taken->approval();
        }

        return PluginLines::under('approval', ...$approvals);
    }

    /** The ones none of these approvals names, in the stack's order. */
    public function leftOutOf(PluginLines $approved): self
    {
        $given = [];

        foreach ($approved as $approval) {
            $given[] = $approval;
        }

        $left = [];

        foreach ($this->taken as $taken) {
            if (! in_array($taken->approval(), $given, strict: true)) {
                $left[] = $taken;
            }
        }

        return new self($left);
    }

    /** Whether there are none. */
    public function isEmpty(): bool
    {
        return $this->taken === [];
    }

    /** @return Traversable<int, AShapeTaken> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->taken);
    }
}
