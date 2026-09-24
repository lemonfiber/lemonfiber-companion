<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One release in the stack's release record.
 *
 * A version string is an identifier rather than an argument, so what travels
 * beside it is whether anyone in the house will notice, what the stack says it
 * delivers, and whether the release stands at all.
 *
 * **Withdrawn is carried, not filtered away.** A stack running a withdrawn
 * release is a thing an operator has to be told, and a history that dropped it
 * would describe releases that did not happen in the order they did.
 */
final readonly class Release
{
    private function __construct(
        private string $version,
        private bool $noticeable,
        private bool $withdrawn,
        private WhatAReleaseDelivers $delivers,
    ) {}

    /**
     * A release the stack named.
     *
     * Refuses a blank version rather than carrying one: a payload short of the
     * name is the stack's half of the conversation gone wrong, not a release
     * with an empty name. Undeclared for the reason the other named values are
     * — a declared throw is a checked one here, and every caller would carry a
     * catch for a fixture it wrote itself.
     */
    public static function called(
        string $version,
        bool $noticeable,
        bool $withdrawn,
        WhatAReleaseDelivers $delivers,
    ): self {
        $named = trim($version);

        if ($named === '') {
            throw VersionIsBlank::inARelease();
        }

        return new self($named, $noticeable, $withdrawn, $delivers);
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * Whether somebody in the house would see the difference.
     *
     * A patch nobody will notice is a different evening from a change to what
     * the household watches.
     */
    public function theHouseholdWouldNotice(): bool
    {
        return $this->noticeable;
    }

    /**
     * What this release changed, as the stack put it.
     *
     * Beside {@see theHouseholdWouldNotice()} rather than instead of it: that
     * says whether this matters to the house, and this says what it is.
     */
    public function delivers(): WhatAReleaseDelivers
    {
        return $this->delivers;
    }

    /** Whether this release has been taken back since it was published. */
    public function wasWithdrawn(): bool
    {
        return $this->withdrawn;
    }
}
