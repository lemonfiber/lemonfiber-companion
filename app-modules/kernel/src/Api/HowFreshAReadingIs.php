<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How far a volume's figures can be relied on.
 *
 * A local disk is read as it is now. A network share answers with what it was
 * last told, so its figures carry the moment they were taken: a figure nobody
 * can refresh is at least dated.
 */
final readonly class HowFreshAReadingIs
{
    private function __construct(private ?Instant $taken) {}

    /** Read off a local disk, so true as of the reading. */
    public static function live(): self
    {
        return new self(null);
    }

    /** Read across a network share, as last told at this moment. */
    public static function asOf(Instant $taken): self
    {
        return new self($taken);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TLive of object
     * @template TAsOf of object
     *
     * @param Closure(): TLive          $live
     * @param Closure(Instant): TAsOf   $asOf
     *
     * @return TLive|TAsOf
     */
    public function either(Closure $live, Closure $asOf): object
    {
        return $this->taken instanceof Instant ? $asOf($this->taken) : $live();
    }
}
