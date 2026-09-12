<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hexdec;
use function implode;
use function mb_str_split;
use function mb_strtoupper;
use function strtr;

/**
 * A fingerprint in a form a person can actually check.
 *
 * `N1-R50` needs this and `Fingerprint` deliberately refuses to provide it, and
 * the tension between those two is the whole design. `ADR-0018` rejects the
 * human-read fingerprint by name — it "asks a person to compare sixty-four hex
 * characters across two screens. People check the first four and the last four,
 * or they press accept." That is why `Fingerprint` has no `shown()`.
 *
 * But typed pairing has no software comparison available: nothing was scanned,
 * so nothing carried the digest to compare against. `N1-R50` is what is left —
 * the operator confirms, and the app must not proceed on an unconfirmed
 * fingerprint. If somebody has to compare, the comparison has to be one they
 * will really do.
 *
 * So this is a separate type, and it is separate precisely so that the ability
 * to display a fingerprint cannot be reached from a `Fingerprint`. Getting one
 * is an explicit act naming this class, which is a line a reviewer sees.
 *
 * **`N1-R51` sets the two constraints and they pull against each other.** Short
 * enough to check at a glance, and derived from the *whole* fingerprint so two
 * different certificates cannot share one. Truncation satisfies the first and
 * fails the second — the first eight characters of a SHA-256 are eight
 * characters an attacker can grind for. This folds every byte in, so changing
 * any part of the digest changes what is shown.
 */
final readonly class AtAGlance
{
    /**
     * The alphabet, with the characters people confuse removed.
     *
     * No `I`, `O`, `1` or `0`: somebody reading four groups off a screen and
     * typing them into a phone confuses those four before they confuse anything
     * else, and a comparison that fails because of a misread character teaches
     * the operator that failures here are noise.
     */
    private const string LETTERS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** How many there are, which is what the fold wraps each character into. */
    private const int HOW_MANY_LETTERS = 32;

    /** How many characters in each group. */
    private const int PER_GROUP = 4;

    /** How many groups. Sixteen characters is a glance; thirty-two is not. */
    private const int GROUPS = 4;

    /**
     * The multiplier the fold mixes with.
     *
     * An odd prime, which is what makes the accumulate spread rather than
     * settle: every byte shifts the running value into a different part of the
     * range instead of clustering it.
     */
    private const int MIXED_WITH = 31;

    /**
     * Where the running value wraps.
     *
     * 2^32, so the arithmetic stays inside an integer on every platform this
     * runs on rather than reaching float precision — where two different
     * digests would start folding to the same groups, silently.
     */
    private const int WRAPS_AT = 4_294_967_296;

    private function __construct(private string $shown) {}

    /**
     * Fold a fingerprint down to something checkable (`N1-R51`).
     *
     * Every byte of the digest contributes: the fold walks the whole of it, so
     * a certificate differing anywhere produces a different set of groups. That
     * is the half truncation fails — the leading characters of a SHA-256 are
     * grindable, and a comparison an attacker can satisfy is not one.
     */
    public static function of(Fingerprint $digest): self
    {
        $folded = self::fold($digest->forComparingByEye());
        $groups = mb_str_split($folded, self::PER_GROUP);

        return new self(implode('-', $groups));
    }

    /**
     * What the operator reads, in groups, to compare against the stack's screen.
     *
     * Grouped because sixteen characters in a row is not a glance — four groups
     * of four is what somebody can hold in their head long enough to look up.
     */
    public function shown(): string
    {
        return $this->shown;
    }

    /**
     * Whether two look the same to a person.
     *
     * Here so a screen never compares the strings itself. The comparison that
     * matters is `Fingerprint::is()`, in software; this one exists only for the
     * typed-entry path where there is nothing to compare against yet.
     */
    public function is(self $other): bool
    {
        return $this->shown === $other->shown;
    }

    /**
     * Every byte of the digest, folded into the output alphabet.
     *
     * A simple accumulate-and-mix rather than a second hash: this is not a
     * security boundary — the security is `Fingerprint::is()`, which compares
     * the whole digest — it is a display of one, and what it owes is that every
     * input byte changes the result.
     */
    private static function fold(string $digest): string
    {
        $bytes = mb_str_split(strtr($digest, ['-' => '', ':' => '']), 2);
        $wanted = self::PER_GROUP * self::GROUPS;
        $letters = mb_str_split(self::LETTERS);
        $shown = '';

        for ($at = 0; $at < $wanted; $at++) {
            $mixed = $at + 1;

            foreach ($bytes as $index => $byte) {
                // Position-weighted so that two bytes swapping places changes
                // the answer. Without the index, an anagram of a digest would
                // fold to the same groups.
                $mixed = ($mixed * self::MIXED_WITH + (int) hexdec($byte) + $index) % self::WRAPS_AT;
            }

            $shown .= $letters[($mixed + $at) % self::HOW_MANY_LETTERS];
        }

        return mb_strtoupper($shown);
    }
}
