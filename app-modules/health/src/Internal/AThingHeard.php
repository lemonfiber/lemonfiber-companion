<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use Modules\Kernel\Api\Instant;

/**
 * What a stream said, when it was heard, and whether it still stands, which a
 * screen never shows apart.
 *
 * {@see ASummaryHeard}'s shape: it stands only while the subscription that
 * carried it is open, so it is heard current and stops being current exactly
 * once.
 *
 * @template-covariant T of object
 */
final readonly class AThingHeard
{
    /** @param T $said */
    private function __construct(
        public object $said,
        public Instant $at,
        public bool $current,
    ) {}

    /**
     * @template U of object
     *
     * @param U $said
     *
     * @return self<U>
     */
    public static function at(object $said, Instant $at): self
    {
        return new self($said, $at, current: true);
    }

    /** @return self<T> */
    public function noLongerCurrent(): self
    {
        return new self($this->said, $this->at, current: false);
    }
}
