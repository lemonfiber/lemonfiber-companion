<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function count;
use function trim;

/**
 * Putting the configuration back: previewed, or carried out.
 *
 * Previewed is what it would revert, with nothing written. Carried out is what
 * it reverted, the operator having said yes. Either way it is the files whose
 * edits go, each with the lines that change, and the connections that go with
 * them. The two are two constructors rather than a flag a caller passes, so
 * which one a screen holds is decided where the stack's answer is read.
 *
 * A preview carries the name the stack gave it, which the yes quotes back so
 * the stack refuses a yes given for files that have moved since.
 */
final readonly class TheReset
{
    private function __construct(
        private TheStackEdits $edits,
        private ConnectionsReverted $connections,
        private bool $carriedOut,
        private string $offer,
    ) {}

    /** What putting the configuration back would revert, under the name the stack gave the preview; nothing was written. */
    public static function previewed(TheStackEdits $edits, ConnectionsReverted $connections, string $offer): self
    {
        if (trim($offer) === '') {
            throw ThereIsNothingToAgreeTo::unnamed();
        }

        return new self($edits, $connections, carriedOut: false, offer: trim($offer));
    }

    /** What putting the configuration back reverted, the operator having said yes. */
    public static function carriedOut(TheStackEdits $edits, ConnectionsReverted $connections): self
    {
        return new self($edits, $connections, carriedOut: true, offer: '');
    }

    /** Whether it was carried out rather than only previewed. */
    public function wasCarriedOut(): bool
    {
        return $this->carriedOut;
    }

    /** The files whose edits go, or went. */
    public function edits(): TheStackEdits
    {
        return $this->edits;
    }

    /** The connections that go, or went, with them. */
    public function connections(): ConnectionsReverted
    {
        return $this->connections;
    }

    /**
     * The name the stack gave the preview, which a yes quotes; `''` once carried out.
     *
     * Published because {@see AResetAgreed} carries it back, and for nothing else.
     */
    public function offer(): string
    {
        return $this->offer;
    }

    /** Whether it reverts no file and no connection. */
    public function changesNothing(): bool
    {
        return count($this->edits) === 0 && count($this->connections) === 0;
    }

    /** Whether it is a preview that would revert something, which is the only reset a yes is given for. */
    public function mayBeAgreedTo(): bool
    {
        return ! $this->carriedOut && ! $this->changesNothing();
    }
}
