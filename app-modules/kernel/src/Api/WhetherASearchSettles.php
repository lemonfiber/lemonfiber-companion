<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether the stack says a search of the indexers would settle a trace, and if so, the search.
 *
 * Two arms rather than a nullable search, so a screen says what it draws for
 * a trace no search would settle rather than finding out.
 */
final readonly class WhetherASearchSettles
{
    private function __construct(private ?ASearchThatSettles $search) {}

    /** A search would settle it, and this is what it spends and covers. */
    public static function so(ASearchThatSettles $search): self
    {
        return new self($search);
    }

    /** No search would settle it. */
    public static function not(): self
    {
        return new self(null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSo of object
     * @template TNot of object
     *
     * @param Closure(ASearchThatSettles): TSo $so
     * @param Closure(): TNot                  $not
     *
     * @return TSo|TNot
     */
    public function either(Closure $so, Closure $not): object
    {
        return $this->search instanceof ASearchThatSettles ? $so($this->search) : $not();
    }
}
