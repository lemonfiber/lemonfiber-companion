<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every bundled setting a plugin declares it will change.
 *
 * @implements IteratorAggregate<int, ASettingItOverrides>
 */
final readonly class TheSettingsItOverrides implements IteratorAggregate
{
    /** @param list<ASettingItOverrides> $overrides */
    private function __construct(private array $overrides) {}

    /** These, in the stack's order. */
    public static function these(ASettingItOverrides ...$overrides): self
    {
        return new self(array_values($overrides));
    }

    /** @return Traversable<int, ASettingItOverrides> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->overrides);
    }
}
