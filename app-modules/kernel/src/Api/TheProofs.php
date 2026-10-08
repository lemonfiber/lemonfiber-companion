<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_all;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every proof that has to hold before a plugin is installed, in the order the install states them.
 *
 * @implements IteratorAggregate<int, AProof>
 */
final readonly class TheProofs implements IteratorAggregate
{
    /** @param list<AProof> $proofs */
    private function __construct(private array $proofs) {}

    /** These, in the stack's order. */
    public static function these(AProof ...$proofs): self
    {
        return new self(array_values($proofs));
    }

    /** Whether no proof among them stops an install: none was asked and failed. */
    public function noneStopsAnInstall(): bool
    {
        return array_all($this->proofs, static fn(AProof $proof): bool => ! $proof->cameTo()->stopsAnInstall());
    }

    /** @return Traversable<int, AProof> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->proofs);
    }
}
