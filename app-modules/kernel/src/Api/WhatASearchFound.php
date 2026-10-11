<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * One page of a member's search, in the order the core answered it.
 *
 * Beside the titles, the core's household sentence on whether what the member
 * asks for waits for whoever runs the house, and where the next page begins
 * where there is one. A title the member's limits keep from them is not on the
 * page at all.
 *
 * @implements IteratorAggregate<int, AFoundTitle>
 */
final readonly class WhatASearchFound implements IteratorAggregate
{
    /** @param list<AFoundTitle> $titles */
    private function __construct(private string $approval, private ?WhereTheNextPageBegins $next, private array $titles) {}

    /** The last page of a search. */
    public static function lastly(string $approval, AFoundTitle ...$titles): self
    {
        return new self($approval, null, array_values($titles));
    }

    /** A page with another after it. */
    public static function before(WhereTheNextPageBegins $next, string $approval, AFoundTitle ...$titles): self
    {
        return new self($approval, $next, array_values($titles));
    }

    /** The core's sentence on whether what the member asks for waits for approval. */
    public function approval(): string
    {
        return $this->approval;
    }

    /** Whether the page holds no title. */
    public function isEmpty(): bool
    {
        return $this->titles === [];
    }

    /**
     * Say what happens where another page follows and where this is the last.
     *
     * @template TMore of object
     * @template TLast of object
     *
     * @param  Closure(WhereTheNextPageBegins): TMore  $more
     * @param  Closure(): TLast  $last
     * @return TMore|TLast
     */
    public function followedBy(Closure $more, Closure $last): object
    {
        return $this->next instanceof WhereTheNextPageBegins ? $more($this->next) : $last();
    }

    /** @return Traversable<int, AFoundTitle> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->titles);
    }
}
