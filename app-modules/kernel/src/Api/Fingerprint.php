<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash_equals;
use function mb_strlen;
use function mb_strtolower;
use function preg_match;

/**
 * The certificate a stack promised to present, as pairing material carried it.
 *
 * `ADR-0018` is the whole design: the fingerprint comes from the same
 * out-of-band payload as the address, never from the network, and the app pins
 * it against that stack and checks every later connection against it — whether
 * or not the platform trust store would accept the certificate. Trust is pinned
 * it to the stack rather than to an address, so reaching the same machine by
 * another route does not re-open the question of its identity.
 *
 * **There is deliberately no way to display one.** The ADR rejects a
 * human-read fingerprint by name: it "asks a person to compare sixty-four hex
 * characters across two screens. People check the first four and the last four,
 * or they press accept." A `shown()` here would be the beginning of exactly
 * that screen. The comparison happens in `is()`, in software, which the ADR
 * says is the only way it happens reliably.
 *
 * **Sixty-four hexadecimal characters, and nothing else is accepted.** Not a
 * guess at a format: a SHA-256 digest is what the stack produces, and a value
 * of any other shape can never match one — pinning it would make a stack that
 * pairs successfully and is then unreachable forever, with the failure arriving
 * as a refused connection rather than as the bad pairing code it was.
 *
 * Case is normalised on the way in and this is the one edit the type makes.
 * Hex has two spellings of every digest and they mean the same certificate; a
 * comparison that called them different would refuse the right machine, which
 * is the failure that becomes "this is not the machine you were
 * introduced to".
 */
final readonly class Fingerprint
{
    /** A SHA-256 digest, which is what a certificate fingerprint is. */
    public const int CHARACTERS = 64;

    private function __construct(private string $digest) {}

    public static function of(string $digest): self
    {
        $length = mb_strlen($digest);

        if ($length !== self::CHARACTERS) {
            throw FingerprintIsNotAFingerprint::ofLength($length);
        }

        if (preg_match('/\A[0-9a-fA-F]+\z/', $digest) !== 1) {
            throw FingerprintIsNotAFingerprint::notHexadecimal();
        }

        return new self(mb_strtolower($digest));
    }

    /**
     * The digest, for folding into a form a person can check.
     *
     * The one reader this type has, and named for the single thing it is for.
     * `ADR-0018` rejects the human-read fingerprint — sixty-four hex characters
     * across two screens, where people check the first four and the last four —
     * so there is deliberately no `shown()`, and this is not it. What it feeds
     * is {@see AtAGlance}, which folds every byte of the digest into sixteen
     * characters somebody will really compare.
     *
     * It exists because typed pairing has no software comparison available:
     * nothing was scanned, so nothing carried a digest to compare against, and
     * The confirmation leaves it to the operator. The name is the guard rail — a
     * call site reading this for any other purpose reads as obviously wrong,
     * which is the same argument `Session::forTheHeader()` makes.
     */
    public function forComparingByEye(): string
    {
        return $this->digest;
    }

    /**
     * Whether this is the certificate that was promised, compared in constant
     * time.
     *
     * `hash_equals` rather than `===`. A fingerprint is public, so the timing
     * of the comparison leaks nothing an attacker cannot already read — but the
     * habit is the point: this comparison sits beside the one on `Session`,
     * where it does matter, and two identity checks written differently invite
     * the wrong one to be copied.
     */
    public function is(self $other): bool
    {
        return hash_equals($this->digest, $other->digest);
    }
}
