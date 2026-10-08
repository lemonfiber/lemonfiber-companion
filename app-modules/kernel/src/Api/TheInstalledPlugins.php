<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every plugin a stack's record holds.
 *
 * @implements IteratorAggregate<int, APlugin>
 */
final readonly class TheInstalledPlugins implements IteratorAggregate
{
    /** @param list<APlugin> $plugins */
    private function __construct(private array $plugins) {}

    /** These, in the stack's order. */
    public static function these(APlugin ...$plugins): self
    {
        return new self(array_values($plugins));
    }

    /** @return Traversable<int, APlugin> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->plugins);
    }
}
