<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every secret a stack's services read, in name order, never with its value.
 *
 * @implements IteratorAggregate<int, ASecret>
 */
final readonly class TheSecretsRead implements IteratorAggregate
{
    /** @param list<ASecret> $secrets */
    private function __construct(private array $secrets) {}

    /** These secrets, in the stack's order. */
    public static function these(ASecret ...$secrets): self
    {
        return new self(array_values($secrets));
    }

    /** Only the secrets one of these services reads. */
    public function readByAnyOf(Services $services): self
    {
        return new self(array_values(array_filter($this->secrets, static fn(ASecret $secret): bool => $secret->readers()->includeAnyOf($services))));
    }

    /** Only the secrets none of these services reads. */
    public function readByNoneOf(Services $services): self
    {
        return new self(array_values(array_filter($this->secrets, static fn(ASecret $one): bool => ! $one->readers()->includeAnyOf($services))));
    }

    /** Only the secrets this owner holds. */
    public function ownedBy(WhoseASettingIs $whose): self
    {
        return new self(array_values(array_filter($this->secrets, static fn(ASecret $one): bool => $one->whose()->is($whose))));
    }

    /** @return Traversable<int, ASecret> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->secrets);
    }
}
