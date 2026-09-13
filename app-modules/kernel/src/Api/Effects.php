<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

use function trim;

/**
 * What a repair touches besides the thing it fixes.
 *
 * The middle clause of `N2-R4`, as a type. The stack sends these as sentences
 * and they are carried without rewording, for the reason {@see Remedy} gives:
 * a layer that rephrases a consequence is a place for it to become subtly
 * wrong, and this is the sentence somebody agrees to something on.
 *
 * A typed collection rather than an array, which is what `D1` asks for, and the
 * order is part of the promise: the stack lists the largest consequence first
 * and a screen that re-sorted would bury it.
 *
 * **Empty is a legitimate value and is not the same as absent.** A repair that
 * affects nothing else is a real answer and {@see self::nothingElse()} is how
 * it is said. What `N2-R4` forbids is a repair whose effects were never
 * established — and that is refused by {@see Repair::offered()} needing one of
 * these rather than by any state this can be in.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class Effects implements IteratorAggregate
{
    /** @param array<int, string> $effects */
    private function __construct(private array $effects) {}

    /**
     * The one place strings become effects.
     *
     * Each is trimmed and a blank one is refused rather than dropped. Dropping
     * would make a stack that sent four consequences render as three, silently,
     * and the operator would agree to the repair having read one fewer than
     * they were sent.
     */
    public static function of(string ...$effects): self
    {
        $named = [];

        foreach ($effects as $effect) {
            $trimmed = trim($effect);

            if ($trimmed === '') {
                throw EffectSaysNothing::inARepair();
            }

            $named[] = $trimmed;
        }

        // No `array_values` here, unlike `Findings::of()`: the loop above
        // builds the list rather than passing the variadic through, so named
        // arguments cannot leave string keys on it.
        return new self($named);
    }

    /** A repair that touches nothing besides what it fixes. */
    public static function nothingElse(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->effects);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->effects);
    }
}
