<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The line as one run of the line's action reported it: rehearsed, applied or only read, with what each download client said and where a lift stands.
 *
 * @implements IteratorAggregate<int, AClientsHolding>
 */
final readonly class TheLineAsChanged implements IteratorAggregate
{
    /** @param list<AClientsHolding> $clients */
    private function __construct(
        private HowTheLineIsShared $line,
        private WhatTheLineRunDid $did,
        private WhereALiftStands $lift,
        private array $clients,
    ) {}

    /** The stack's report, each client in the order it declared them. */
    public static function said(HowTheLineIsShared $line, WhatTheLineRunDid $did, WhereALiftStands $lift, AClientsHolding ...$clients): self
    {
        return new self($line, $did, $lift, array_values($clients));
    }

    /** How the line is shared, as this run left it or would leave it. */
    public function line(): HowTheLineIsShared
    {
        return $this->line;
    }

    /** Whether this run rehearsed the change, applied it, or only read. */
    public function did(): WhatTheLineRunDid
    {
        return $this->did;
    }

    /** Where a lift of the limits stands. */
    public function lift(): WhereALiftStands
    {
        return $this->lift;
    }

    /** Whether the stack runs any download client to limit. */
    public function hasClients(): bool
    {
        return $this->clients !== [];
    }

    /** @return Traversable<int, AClientsHolding> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->clients);
    }
}
