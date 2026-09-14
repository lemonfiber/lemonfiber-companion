<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One version the stack could be on, and what taking it would mean here.
 *
 * Carries what `N2-R16`'s decision needs and nothing else. A version string is
 * an identifier rather than an argument — nobody decides an evening on `4.0.15`
 * — so what travels beside it is whether anyone in the house will notice, and
 * whether the release stands at all.
 *
 * **Withdrawn is not a kind of available.** `N2-R16` refuses to present one as
 * an update, and the reason it is carried here rather than filtered away
 * upstream is that a stack running a withdrawn release is a thing an operator
 * has to be told: silently dropping it from a list would leave them reading a
 * screen that says nothing is wrong.
 */
final readonly class Release
{
    private function __construct(
        private string $version,
        private bool $noticeable,
        private bool $withdrawn,
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
    public static function called(string $version, bool $noticeable, bool $withdrawn): self
    {
        $named = trim($version);

        if ($named === '') {
            throw VersionIsBlank::inARelease();
        }

        return new self($named, $noticeable, $withdrawn);
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * Whether somebody in the house would see the difference.
     *
     * The distinction `N2-R16` is about. A screen sorts on it and an operator
     * decides on it; a patch nobody will notice is a different evening from a
     * change to what the household watches.
     */
    public function theHouseholdWouldNotice(): bool
    {
        return $this->noticeable;
    }

    /** Whether this release has been taken back since it was published. */
    public function wasWithdrawn(): bool
    {
        return $this->withdrawn;
    }

    /**
     * Whether this is something to offer.
     *
     * Asked here rather than by each caller, because `N2-R16`'s refusal is one
     * rule and a screen that reimplemented it would be a second place to
     * forget.
     */
    public function isWorthOffering(): bool
    {
        return ! $this->withdrawn;
    }

}
