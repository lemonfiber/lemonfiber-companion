<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a running copy is writing, slowed to the operator's limit, or stalled, as the stack read it.
 */
final readonly class HowTheCopyMoves
{
    private function __construct(private ?int $limit, private ?Instant $wrote) {}

    /** It is writing as fast as it can. */
    public static function working(): self
    {
        return new self(null, null);
    }

    /** It is held to the operator's limit, in bytes a second. */
    public static function paced(int $limit): self
    {
        return new self($limit, null);
    }

    /** It has written nothing since then, for longer than the operator's minutes. */
    public static function stalled(Instant $wrote): self
    {
        return new self(null, $wrote);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template R of object
     *
     * @param Closure(): R         $working
     * @param Closure(int): R      $paced
     * @param Closure(Instant): R  $stalled
     *
     * @return R
     */
    public function either(Closure $working, Closure $paced, Closure $stalled): object
    {
        return match (true) {
            $this->wrote instanceof Instant => $stalled($this->wrote),
            $this->limit !== null => $paced($this->limit),
            default => $working(),
        };
    }
}
