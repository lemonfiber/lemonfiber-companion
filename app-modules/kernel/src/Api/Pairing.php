<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_key_exists;
use function array_keys;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function preg_replace;
use function strtr;
use function trim;

/**
 * What a stack hands somebody out of band so their phone can find it and know it.
 *
 * Where the stack is and which certificate it will present, and the pairing of
 * those two is the whole point; with them, which stack it is, in its own
 * words, so a second code for the same machine names the same machine. The design is this —
 * **the fingerprint comes from this material and never from the network**,
 * because a fingerprint learned from the connection it is meant to
 * validate proves nothing at all. Somebody carries it across the gap by eye or
 * by camera, and that gap is what makes it trustworthy.
 *
 * **Both routes in.** A camera reads it, or a person types it, and
 * {@see HowItWasRead} records which — not to change the parsing, which is
 * identical, but because the two fail differently and a screen has to say so.
 * Typed entry is not a courtesy: it is the route on a device with no camera and
 * on one whose operator declined the permission, which has to
 * exist.
 *
 * **It holds no session and no credential.** What it carries is public in the
 * sense that matters — an address on somebody's network and a hash of a
 * certificate that machine is about to present to anybody who connects. That is
 * why this type has readers and {@see Session} does not: it keeps pairing
 * material out of caches, and an adapter has to be able to read it to act on it.
 */
final readonly class Pairing
{
    /**
     * The quotation marks a keyboard types in place of `"`, and the `"` each stands for.
     *
     * iOS Smart Punctuation turns a typed `"` into an opening or closing mark
     * for the keyboard's language, and a Japanese keyboard types the
     * full-width one. None of them is JSON, and none can be part of what a
     * stack writes, so each is read as the `"` it was typed for.
     */
    private const array QUOTES_AS_TYPED = [
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{201E}" => '"',
        "\u{00AB}" => '"',
        "\u{00BB}" => '"',
        "\u{FF02}" => '"',
    ];

    /** Every run of whitespace, line breaks and no-break spaces included. */
    private const string WHITESPACE = '/\s+/u';

    private function __construct(
        private Address $at,
        private Fingerprint $presenting,
        private StackId $stack,
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
     * **No `@throws`, deliberately**, for the reason {@see Configured::stack()}
     * gives. The refusal is named in the prose above instead.
     */
    public static function read(string $said, HowItWasRead $how, Clock $clock): self
    {
        $found = json_decode(self::asMeant($said), associative: true);

        if (! is_array($found)) {
            throw PairingIsNotReadable::fromWhatWasRead($how);
        }

        foreach (array_keys($found) as $key) {
            // `WhatPairingMaterialSays` is the only place the keys are
            // spelled. A `tryFrom` answering null is read here and nowhere
            // else, which is what keeps the spelling from being written twice.
            //
            // Cast because a JSON array decodes to integer keys, and `[1, 2]`
            // is a payload somebody can send. Refused like any other key the
            // format does not define, and named in the refusal.
            $name = (string) $key;

            if (WhatPairingMaterialSays::tryFrom($name) === null) {
                throw PairingIsNotReadable::carrying($name, $how);
            }
        }

        // Read before the address and the fingerprint, because material that
        // is too old should not be reported as material that is malformed. A
        // stale code with a typo in its address is stale first — telling
        // somebody to check what they scanned, when what they need is a new
        // code, is the screen expiry is about.
        self::stillGood($found, $how, $clock);

        // `Address::of()` and `Fingerprint::of()` do their own refusing, and
        // this deliberately does not catch them. A malformed address inside
        // well-formed material is that type's refusal to explain, not this
        // one's — and wrapping it would replace a message naming the problem
        // with one naming the envelope.
        $at = Address::of(self::halfOf($found, WhatPairingMaterialSays::Address, $how));

        // Material that promises a certificate for an address presenting none.
        // The fingerprint is "the certificate that address will
        // present", and an unencrypted address presents nothing — so the digest
        // would be pinned against a connection with nothing to compare, and
        // the pin could never be kept for this stack.
        //
        // Here rather than at the first connection, which is where it landed:
        // pairing succeeded, the stack was written down, and `BaseUrl::pinned()`
        // raised a configuration problem from inside the transport, on a device,
        // after the operator had been told they were paired. This is the one
        // moment the material is in front of somebody who can go and get
        // better material.
        if (! $at->isEncrypted()) {
            throw PairingIsNotReadable::withAnAddressThatPresentsNothing($how);
        }

        return new self(
            $at,
            Fingerprint::of(self::halfOf($found, WhatPairingMaterialSays::Fingerprint, $how)),
            StackId::saidBy(self::halfOf($found, WhatPairingMaterialSays::Stack, $how)),
            $how,
        );
    }

    /** Where the stack said it is. */
    public function at(): Address
    {
        return $this->at;
    }

    /**
     * The certificate the stack promised to present.
     *
     * Taken from here and pinned against the stack, then checked on every later
     * connection whether or not the platform's trust store would accept it.
     */
    public function presenting(): Fingerprint
    {
        return $this->presenting;
    }

    /**
     * Which machine this is, as the stack names itself.
     *
     * The same across every code the stack issues, so this is what decides
     * whether the machine is one this device already holds.
     */
    public function stack(): StackId
    {
        return $this->stack;
    }

    /** How the operator got it in, which decides what a failure should say. */
    public function how(): HowItWasRead
    {
        return $this->how;
    }

    /**
     * The material with what a keyboard or a paste added taken out.
     *
     * Every value a stack writes is an address, a hexadecimal digest, a
     * hexadecimal identifier or a number, so whitespace in material sits only
     * between its parts, where JSON ignores it. Removing all of it reads a code
     * wrapped across lines, pasted with a trailing newline or typed with
     * spaces the same as the line the stack wrote. A curly quotation mark is
     * read as the `"` it replaced.
     *
     * Material that is not valid UTF-8 comes out empty, and empty material is
     * refused like any other that is not JSON.
     */
    private static function asMeant(string $said): string
    {
        return (string) preg_replace(self::WHITESPACE, '', strtr($said, self::QUOTES_AS_TYPED));
    }

    /**
     * Refuses material that has expired.
     *
     * The check is here, at the one moment a payload becomes a `Pairing`,
     * rather than on a reader the caller is trusted to ask. A type that can
     * exist in an expired state is a type somebody holds past its expiry, and
     * the whole point of the expiry is that nothing acts on stale material.
     *
     * Expiring *at* the instant counts as expired. A boundary that admits the
     * exact second is a boundary two clocks disagree about.
     *
     * @param array<array-key, mixed> $found
     */
    private static function stillGood(array $found, HowItWasRead $how, Clock $clock): void
    {
        $when = WhatPairingMaterialSays::Expires;

        if (! array_key_exists($when->value, $found)) {
            throw PairingIsNotReadable::withoutIts($when, $how);
        }

        $said = $found[$when->value];

        // An integer, and not a string holding one. A format that takes both
        // has two spellings of the same fact, and the day a producer switches
        // spelling is the day every app that only handled the other reports
        // material it was handed correctly as malformed.
        //
        // Nothing checks the sign here. `Instant::atEpochSeconds()` refuses a
        // moment before the epoch and says so, and a second check would be the
        // same rule written twice — with the copy answering a worse sentence.
        // That is the reasoning `read()` already gives for letting `Address`
        // and `Fingerprint` do their own refusing.
        if (! is_int($said)) {
            throw PairingIsNotReadable::withoutIts($when, $how);
        }

        $expires = Instant::atEpochSeconds($said);

        if (! $clock->now()->isBefore($expires)) {
            throw PairingIsSpent::since($expires, $how);
        }
    }

    /**
     * One half of the material, or a refusal naming which half was missing.
     *
     * Written out rather than `$found['address'] ?? null`: a coalesce on an
     * array turns three different situations — absent, present and null,
     * present and the wrong type — into one, and the one it picks is the one
     * that reads as "carry on". Here each of the three means the same thing and
     * the refusal says so explicitly.
     *
     * @param array<array-key, mixed> $found
     */
    private static function halfOf(array $found, WhatPairingMaterialSays $half, HowItWasRead $how): string
    {
        if (! array_key_exists($half->value, $found)) {
            throw PairingIsNotReadable::withoutIts($half, $how);
        }

        $said = $found[$half->value];

        if (! is_string($said) || trim($said) === '') {
            throw PairingIsNotReadable::withoutIts($half, $how);
        }

        return $said;
    }
}
