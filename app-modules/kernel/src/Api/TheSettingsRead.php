<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
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

    /** Only the settings none of these services reads. */
    public function readByNoneOf(Services $services): self
    {
        return new self(array_values(array_filter($this->settings, static fn(ASetting $one): bool => ! $one->readers()->includeAnyOf($services))));
    }

    /** Only the settings this owner holds. */
    public function ownedBy(WhoseASettingIs $whose): self
    {
        return new self(array_values(array_filter($this->settings, static fn(ASetting $one): bool => $one->whose()->is($whose))));
    }

    /** The setting of that name, or none. */
    /** The settings that hang together with any of these, apart from these themselves, in the order it holds them. */
    public function hangingTogetherWith(ASetting ...$moved): self
    {
        return new self(array_values(array_filter(
            $this->settings,
            static fn(ASetting $one): bool => ! array_any($moved, static fn(ASetting $setting): bool => $setting->name() === $one->name())
                && array_any($moved, static fn(ASetting $setting): bool => $one->hangsTogetherWith($setting)),
        )));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->settings);
    }
}
