<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;
use function array_replace;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * The values other settings have to hold for a value to be a choice: each setting with the values it has to hold one of.
 *
 * @implements IteratorAggregate<string, list<string>>
 */
final readonly class WhatAChoiceNeeds implements IteratorAggregate
{
    /** @param array<string, list<string>> $needs */
    private function __construct(private array $needs) {}

    /** One setting holding one of these values; a blank setting is refused. */
    public static function holding(string $setting, string ...$values): self
    {
        if (trim($setting) === '') {
            throw SettingSaysNothing::about('needs');
        }

        return new self([trim($setting) => array_values($values)]);
    }

    /** Nothing: a value that is always a choice. */
    public static function nothing(): self
    {
        return new self([]);
    }

    /** What both need, the other's word on a setting both name standing. */
    public function and(self $other): self
    {
        return new self(array_replace($this->needs, $other->needs));
    }

    /** Whether it names this setting. */
    public function names(ASetting $setting): bool
    {
        return array_key_exists($setting->name(), $this->needs);
    }

    /** @return Traversable<string, list<string>> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->needs);
    }
}
