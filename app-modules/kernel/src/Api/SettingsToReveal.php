<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The settings a support bundle shows as they are: the ones the operator
 * agreed to, and the ones a bundle states it reveals.
 *
 * Grown one setting at a time and never all at once: there is no way to build
 * one holding several except by adding each, which is the only way the screen
 * offers. A setting added twice is held once.
 *
 * @implements IteratorAggregate<int, ASettingToReveal>
 */
final readonly class SettingsToReveal implements Countable, IteratorAggregate
{
    /** @param list<ASettingToReveal> $settings */
    private function __construct(private array $settings) {}

    /** Nothing revealed, which is what a bundle is unless somebody asks. */
    public static function none(): self
    {
        return new self([]);
    }

    /** These, and one more agreed to. */
    public function with(ASettingToReveal $setting): self
    {
        return $this->holds($setting) ? $this : new self([...$this->settings, $setting]);
    }

    /** These, without one the operator took back. */
    public function without(ASettingToReveal $setting): self
    {
        $kept = [];

        foreach ($this->settings as $held) {
            if (! $held->is($setting)) {
                $kept[] = $held;
            }
        }

        return new self($kept);
    }

    /** Whether this setting is among them. */
    public function holds(ASettingToReveal $setting): bool
    {
        return array_any($this->settings, static fn(ASettingToReveal $held): bool => $held->is($setting));
    }

    /** @return Traversable<int, ASettingToReveal> in the order they were agreed to */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->settings);
    }

    /** How many, which is none unless somebody agreed to one. */
    public function count(): int
    {
        return count($this->settings);
    }
}
