<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every value a setting accepts, in the stack's order, or none where its services take any value.
 *
 * @implements IteratorAggregate<int, AChoice>
 */
final readonly class TheChoices implements IteratorAggregate
{
    /** @param list<AChoice> $choices */
    private function __construct(private array $choices) {}

    /** These values, in the stack's order. */
    public static function these(AChoice ...$choices): self
    {
        return new self(array_values($choices));
    }

    /** None: its services take any value. */
    public static function none(): self
    {
        return new self([]);
    }

    /** Whether it lists none, so a value is typed rather than chosen. */
    public function isEmpty(): bool
    {
        return $this->choices === [];
    }

    /** Whether any of its values is a choice only while this setting holds one of the values it names. */
    public function hangOn(ASetting $setting): bool
    {
        return array_any($this->choices, static fn(AChoice $choice): bool => $choice->needs()->names($setting));
    }

    /** @return Traversable<int, AChoice> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->choices);
    }
}
