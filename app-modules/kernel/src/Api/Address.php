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
 */
final readonly class Address implements JsonSerializable
{
    private function __construct(private string $url, private string $scheme) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['url' => '(a stack address, hidden)'];
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
        $scheme = parse_url($trimmed, PHP_URL_SCHEME);

        if (! is_string($scheme)) {
            throw AddressIsUnreachable::withoutAScheme();
        }

        return new self($trimmed, mb_strtolower($scheme));
    }

    /** The value the client dials, and nothing else. */
    public function forTheClient(): string
    {
        return $this->url;
    }

    /**
     * Whether what travels to this address is encrypted.
     *
     * The question `N1-R12` asks, answered from the one fact that decides it.
     * A screen that worked this out for itself would be a second implementation
     * of a one-line rule, and the two would disagree about the case nobody
     * thought of.
     */
    public function isEncrypted(): bool
    {
        return $this->scheme === 'https';
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
