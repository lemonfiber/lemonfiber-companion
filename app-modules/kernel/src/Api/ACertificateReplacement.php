<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What replacing the media server's internal certificate would do, as the stack read it, or what it came to once agreed to.
 *
 * Read first: the reading says in the stack's words every service handed the
 * new certificate's SHA-256 and whether the media server restarts, and goes by
 * the name a yes quotes. Carried out, it says where it settled, in the stack's
 * words, and how far it reached each holder.
 */
final readonly class ACertificateReplacement
{
    private function __construct(
        private string $agreement,
        private WhereAReplacementSettled $settled,
        private string $said,
        private TheHoldersReached $holders,
    ) {}

    /** The reading: nothing replaced yet, what it would do in the stack's words, under the name a yes quotes. */
    public static function read(string $agreement, string $said): self
    {
        return new self(trim($agreement), WhereAReplacementSettled::Rehearsed, trim($said), TheHoldersReached::of());
    }

    /** Where a replacement settled, what the stack said of it, and how far it reached each holder. */
    public static function settled(WhereAReplacementSettled $settled, string $said, AHolderReached ...$holders): self
    {
        return new self('', $settled, trim($said), TheHoldersReached::of(...$holders));
    }

    /** The name the reading goes by, which a yes quotes; empty on anything but a reading. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Where it settled; a reading settled nowhere and says it was rehearsed. */
    public function settledAt(): WhereAReplacementSettled
    {
        return $this->settled;
    }

    /** What the stack said of it, in its own words; empty where it said nothing. */
    public function said(): string
    {
        return $this->said;
    }

    /** Each holder, and how far the replacement reached it. */
    public function holders(): TheHoldersReached
    {
        return $this->holders;
    }

    /** Whether this is a reading a yes may quote. */
    public function isAReading(): bool
    {
        return $this->settled === WhereAReplacementSettled::Rehearsed && $this->agreement !== '';
    }

    /** Whether it landed: proven, and the one in force. */
    public function landed(): bool
    {
        return $this->settled === WhereAReplacementSettled::Replaced;
    }
}
