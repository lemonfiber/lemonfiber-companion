<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A support bundle, as the stack described or wrote it.
 *
 * One value for both, as the stack answers both with one: what it holds, how
 * large it is, what could not be collected and when it was taken are the same
 * answer at two moments, and {@see WhereABundleIs} is what says which moment
 * this is.
 */
final readonly class ABundle
{
    private function __construct(
        private int $bytes,
        private WhereABundleIs $where,
        private TheTermsOfABundle $terms,
        private ThePiecesOfABundle $pieces,
        private Remarks $missing,
        private WhenABundleWasTaken $taken,
    ) {}

    /** The stack's account of one bundle. */
    public static function reported(
        int $bytes,
        WhereABundleIs $where,
        TheTermsOfABundle $terms,
        ThePiecesOfABundle $pieces,
        Remarks $missing,
        WhenABundleWasTaken $taken,
    ): self {
        return new self($bytes, $where, $terms, $pieces, $missing, $taken);
    }

    /** How large the file is, or would be. */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /** Where it would go, or went. */
    public function where(): WhereABundleIs
    {
        return $this->where;
    }

    /** How it was made, as the bundle states it. */
    public function terms(): TheTermsOfABundle
    {
        return $this->terms;
    }

    /** Every file it holds. */
    public function pieces(): ThePiecesOfABundle
    {
        return $this->pieces;
    }

    /** What could not be collected, in the stack's words, which may be nothing. */
    public function missing(): Remarks
    {
        return $this->missing;
    }

    /** When it was taken, and from which versions. */
    public function taken(): WhenABundleWasTaken
    {
        return $this->taken;
    }
}
