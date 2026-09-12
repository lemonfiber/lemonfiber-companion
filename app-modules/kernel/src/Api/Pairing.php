<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function trim;

/**
 * What a stack hands somebody out of band so their phone can find it and know it.
 *
 * Two things, and the pairing of them is the whole point: where the stack is,
 * and which certificate it will present. `ADR-0018` is the design —
 * **the fingerprint comes from this material and never from the network**
 * (`N1-R18`), because a fingerprint learned from the connection it is meant to
 * validate proves nothing at all. Somebody carries it across the gap by eye or
 * by camera, and that gap is what makes it trustworthy.
 *
 * **Both routes in (`N1-R6`).** A camera reads it, or a person types it, and
 * {@see HowItWasRead} records which — not to change the parsing, which is
 * identical, but because the two fail differently and a screen has to say so.
 * Typed entry is not a courtesy: it is the route on a device with no camera and
 * on one whose operator declined the permission, which `N4-R3` requires to
 * exist.
 *
 * **It holds no session and no credential.** What it carries is public in the
 * sense that matters — an address on somebody's network and a hash of a
 * certificate that machine is about to present to anybody who connects. That is
 * why this type has readers and {@see Session} does not: `N1-R23` keeps pairing
 * material out of caches, and an adapter has to be able to read it to act on it.
 */
final readonly class Pairing
{
    private function __construct(
        private Address $at,
        private Fingerprint $presenting,
        private HowItWasRead $how,
    ) {}

    /**
     * The one place a payload becomes a pairing.
     *
     * Takes the route as well as the text so that a refusal can say which it
     * was. Raises rather than answering with a refusal type, which is this
     * codebase's exception rather than its rule: there is no half-paired stack
     * to carry on with, so there is nothing for a caller to do with a value
     * except stop.
     *
     * **No `@throws`, deliberately.** The house rule is written in `Problems`:
     * an `@throws` makes an exception *checked* to the analyser, shipmonk
     * forbids raising a checked exception inside any closure, and every Pest
     * test body is a closure — so annotating this would make the refusal the one
     * behaviour no test could exercise. The refusal is named in the prose above,
     * where a reader finds it, rather than in a tag that would take the test
     * away.
     */
    public static function read(string $said, HowItWasRead $how): self
    {
        $found = json_decode($said, associative: true);

        if (! is_array($found)) {
            throw PairingIsNotReadable::fromWhatWasRead($how);
        }

        // `Address::of()` and `Fingerprint::of()` do their own refusing, and
        // this deliberately does not catch them. A malformed address inside
        // well-formed material is that type's refusal to explain, not this
        // one's — and wrapping it would replace a message naming the problem
        // with one naming the envelope.
        return new self(
            Address::of(self::halfOf($found, 'address', $how)),
            Fingerprint::of(self::halfOf($found, 'fingerprint', $how)),
            $how,
        );
    }

    /** Where the stack said it is. */
    public function at(): Address
    {
        return $this->at;
    }

    /**
     * The certificate the stack promised to present (`N1-R18`, `N1-R19`).
     *
     * Taken from here and pinned against the stack, then checked on every later
     * connection whether or not the platform's trust store would accept it.
     */
    public function presenting(): Fingerprint
    {
        return $this->presenting;
    }

    /** How the operator got it in, which decides what a failure should say. */
    public function how(): HowItWasRead
    {
        return $this->how;
    }

    /**
     * One half of the material, or a refusal naming which half was missing.
     *
     * Written out rather than `$found['address'] ?? null`, which the analyser
     * refuses by name: a coalesce on an array turns three different situations —
     * absent, present and null, present and the wrong type — into one, and the
     * one it picks is the one that reads as "carry on". Here each of the three
     * means the same thing and the refusal says so explicitly, which is the
     * difference between a rule followed and a rule satisfied.
     *
     * @param array<array-key, mixed> $found
     */
    private static function halfOf(array $found, string $half, HowItWasRead $how): string
    {
        if (! array_key_exists($half, $found)) {
            throw PairingIsNotReadable::withoutIts($half, $how);
        }

        $said = $found[$half];

        if (! is_string($said) || trim($said) === '') {
            throw PairingIsNotReadable::withoutIts($half, $how);
        }

        return $said;
    }
}
