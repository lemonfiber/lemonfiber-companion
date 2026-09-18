<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_slice;
use function count;
use function hash;
use function implode;
use function intval;
use function mb_str_split;

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
     *
     * How many there are is *counted* rather than written down beside it. It was
     * written down — `HOW_MANY_LETTERS = 32` — which is the same fact twice, and
     * the copy drifts the first time somebody removes a character they have
     * decided is also confusable. Shrink the alphabet and the fold indexes past
     * the end; grow it and the letters past the thirty-second are never chosen,
     * which weakens the spread silently. Neither failure names this line.
     */
    private const string LETTERS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** How many characters in each group. */
    private const int PER_GROUP = 4;

    /** How many groups. Sixteen characters is a glance; thirty-two is not. */
    private const int GROUPS = 4;

    /**
     * What the digest is hashed with before it is rendered.
     *
     * SHA-256, the same function that produced the fingerprint. Not because
     * this is a security boundary — `Fingerprint::is()` is — but because a
     * spread nobody has to verify by reading is worth more here than a clever
     * one. The arithmetic this replaced was four lines long, looked reasonable,
     * and folded every certificate in the world into thirty-two strings.
     */
    private const string MIXED_BY = 'sha256';

    /** How many hexadecimal characters spell one byte. */
    private const int PER_BYTE = 2;

    /** The base the hash is spelled in. */
    private const int HEX = 16;

    private function __construct(private string $shown) {}

    /**
     * Fold a fingerprint down to something checkable.
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
     * **A second hash, and the reason is a bug this replaced.** What was here
     * was a hand-rolled accumulate-and-mix, argued for in this docblock on the
     * grounds that "this is not a security boundary". It produced **thirty-two
     * distinct codes in total** — every certificate in the world folded to one
     * of thirty-two strings, each a walk through the alphabet in steps of two.
     *
     * The arithmetic collapsed. The loop ran the same mix over the same bytes
     * for all sixteen positions, so the only thing separating one position from
     * the next was the seed `$at + 1`; after thirty-two rounds of `m * 31` that
     * seed arrives multiplied by `31 ** 32`, and `31 ** 32 % 32 == 1` because 31
     * is −1 modulo 32. The position index came out as a fixed arithmetic walk
     * with one free number in it, and sixteen characters carried five bits.
     *
     * That is `N1-R51` failing in the exact way its own docblock warns about:
     * two certificates share a code one time in thirty-two, and an attacker
     * grinding a certificate to match a shown one needs about sixteen tries —
     * far cheaper than the truncation this class rejects by name.
     *
     * So the digest is hashed and the hash is rendered. Sixteen bytes of
     * SHA-256 across five bits each is the eighty bits the display can hold,
     * every one of them moved by every input byte, and it is arithmetic nobody
     * has to check by reading. The security is still `Fingerprint::is()`; what
     * this owes is that two different certificates do not look the same, and
     * this is the version that pays it.
     */
    private static function fold(string $digest): string
    {
        $letters = mb_str_split(self::LETTERS);
        $wanted = self::PER_GROUP * self::GROUPS;
        $shown = '';

        // The whole digest goes in, so a certificate differing anywhere lands
        // on different bytes here. Sixteen of the hash's thirty-two are read,
        // which is not the truncation this class refuses — that one cut the
        // *input* short and left the rest of the certificate unable to change
        // the answer. Every byte of the digest moves every byte of this hash.
        $bytes = mb_str_split(hash(self::MIXED_BY, $digest), self::PER_BYTE);

        foreach (array_slice($bytes, 0, $wanted) as $byte) {
            // The low five bits of each byte. Even, with no modulo bias to
            // argue about, because the alphabet holds exactly thirty-two.
            $shown .= $letters[intval($byte, self::HEX) % count($letters)];
        }

        return $shown;
    }
}
