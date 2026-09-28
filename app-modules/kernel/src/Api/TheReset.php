<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function count;

/**
 * Putting the configuration back: previewed, or carried out.
 *
 * Previewed is what it would revert, with nothing written. Carried out is what
 * it reverted, the operator having said yes. Either way it is the files whose
 * edits go, each with the lines that change, and the connections that go with
 * them. The two are two constructors rather than a flag a caller passes, so
 * which one a screen holds is decided where the stack's answer is read.
 */
final readonly class TheReset
{
    private function __construct(
        private EditsReverted $edits,
        private ConnectionsReverted $connections,
        private bool $carriedOut,
    ) {}

    /** What putting the configuration back would revert; nothing was written. */
    public static function previewed(EditsReverted $edits, ConnectionsReverted $connections): self
    {
        return new self($edits, $connections, carriedOut: false);
    }

    /** What putting the configuration back reverted, the operator having said yes. */
    public static function carriedOut(EditsReverted $edits, ConnectionsReverted $connections): self
    {
        return new self($edits, $connections, carriedOut: true);
    }

    /** Whether it was carried out rather than only previewed. */
    public function wasCarriedOut(): bool
    {
        return $this->carriedOut;
    }

    /** The files whose edits go, or went. */
    public function edits(): EditsReverted
    {
        return $this->edits;
    }

    /** The connections that go, or went, with them. */
    public function connections(): ConnectionsReverted
    {
        return $this->connections;
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
