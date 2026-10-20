<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every setting a stack's services read, in name order.
 *
 * @implements IteratorAggregate<int, ASetting>
 */
final readonly class TheSettingsRead implements IteratorAggregate
{
    /** @param list<ASetting> $settings */
    private function __construct(private array $settings) {}

    /** These settings, in the stack's order. */
    public static function these(ASetting ...$settings): self
    {
        return new self(array_values($settings));
    }

    /** Only the settings one of these services reads. */
    public function readByAnyOf(Services $services): self
    {
        return new self(array_values(array_filter($this->settings, static fn(ASetting $setting): bool => $setting->readers()->includeAnyOf($services))));
    }

    /** @return Traversable<int, ASetting> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->settings);
    }
}
