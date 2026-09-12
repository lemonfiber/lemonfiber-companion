<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function is_string;

use JsonSerializable;

use function mb_strtolower;
use function parse_url;

use const PHP_URL_SCHEME;

use function trim;

/**
 * Where a stack is, as pairing material carried it.
 *
 * Treated like a secret and it is not one, which is worth saying plainly.
 * `N1-R15` names a stack address in the same breath as a credential and a
 * session token: never logged, never transmitted, never in a diagnostic
 * report. The reason is not confidentiality — it is that an address is where
 * somebody lives, and a support bundle full of them is a map of private
 * networks. So this carries the same redaction `Session` does, and for a
 * different reason.
 *
 * The only accessor is named for where the value goes. `N1-R8` keeps the
 * session out of the URL, and this is the other half of that: a type whose
 * single reader says `forTheClient()` makes building a string out of an address
 * for any other purpose read wrong at the call site.
 *
 * **Whether the connection is encrypted is read off the scheme, here.**
 * `N1-R12` says the app must state it and must not imply protection it does not
 * have, and the answer is a property of the address rather than a judgement a
 * screen makes — so it is answered once, where the address is, instead of by
 * each screen that wants to show a padlock.
 *
 * Plain `http` is accepted rather than refused. A stack on a local network may
 * genuinely be reached that way, and refusing the address would tell an
 * operator their pairing code is broken when what is true is that their
 * connection is not private. That distinction is exactly what `N1-R12` exists
 * to keep, and it is lost if the value cannot be constructed.
 *
 * A scheme {@see Scheme} does not name is refused, which is the other side of
 * the same coin: `file://` is not an insecure stack, it is not a stack.
 */
final readonly class Address implements JsonSerializable
{
    private function __construct(private string $url, private Scheme $scheme) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['url' => '(a stack address, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * `__debugInfo()` above answers the readers that are people. This answers
     * the one that is code, and the answer is a refusal — see
     * {@see MustNotLeaveThisProcess} for why it is not a redaction.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::anAddress();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * The other half of the same door. Without it a crafted payload naming this
     * class would be walked back into an object with whatever properties it
     * carried, which is a Address nobody constructed.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::anAddress();
    }

    public static function of(string $url): self
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            throw AddressIsUnreachable::blank();
        }

        // `parse_url` answers null where there is no scheme and false where the
        // address is malformed, and never an empty string — so `is_string` is the
        // whole check. An `|| $scheme === ''` beside it reads like caution and is
        // a branch no input can reach, which is a line no test can defend and no
        // mutant can be killed on.
        $said = parse_url($trimmed, PHP_URL_SCHEME);

        if (! is_string($said)) {
            throw AddressIsUnreachable::withoutAScheme();
        }

        // RFC 3986 calls the scheme case-insensitive, and a QR code encoder that
        // upper-cases the whole payload to shorten it is a real thing — so the
        // case is normalised before the vocabulary is consulted rather than a
        // second case being added to it.
        $scheme = Scheme::tryFrom(mb_strtolower($said))
            ?? throw AddressIsUnreachable::withAnUnknownScheme();

        return new self($trimmed, $scheme);
    }

    /** The value the client dials, and nothing else. */
    public function forTheClient(): string
    {
        return $this->url;
    }

    /**
     * Whether what travels to this address is encrypted.
     *
     * The question `N1-R12` asks, handed to the type that owns the answer. This
     * is not a second implementation of {@see Scheme::isEncrypted()} — it is the
     * address saying which scheme to ask, which is the only part of the question
     * an address knows.
     */
    public function isEncrypted(): bool
    {
        return $this->scheme->isEncrypted();
    }

    public function is(self $other): bool
    {
        return $this->url === $other->url;
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return '(a stack address, hidden)';
    }
}
