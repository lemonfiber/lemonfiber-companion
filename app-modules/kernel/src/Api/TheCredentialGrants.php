<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Every grant of a credential to a third-party plugin, in the stack's order.
 *
 * None at all is an answer: a stack whose plugins are first-party grants none.
 *
 * @implements IteratorAggregate<int, ACredentialGrant>
 */
final readonly class TheCredentialGrants implements Countable, IteratorAggregate
{
    /** @param list<ACredentialGrant> $grants */
    private function __construct(private array $grants) {}

    /** These grants, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ACredentialGrant ...$grants): self
    {
        return new self(array_values($grants));
    }

    /** @return Traversable<int, ACredentialGrant> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->grants);
    }

    public function count(): int
    {
        return count($this->grants);
    }
}
