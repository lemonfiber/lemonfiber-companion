<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function count;
use function explode;

use const FILTER_VALIDATE_IP;

use function filter_var;
use function is_int;
use function is_numeric;
use function is_string;

use JsonSerializable;

use function ltrim;
use function mb_strtolower;
use function parse_url;

use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function trim;

/**
 * Where a stack is, as pairing material carried it.
 *
 * Treated like a secret and it is not one, which is worth saying plainly.
 * A stack address is named in the same breath as a credential and a
 * session token: never logged, never transmitted, never in a diagnostic
 * report, and shown only to the operator, on the screen that says it was
 * tried and not reached. The reason is not confidentiality — it is that an
 * address is where somebody lives, and a support bundle full of them is a map
 * of private networks. So this carries the same redaction `Session` does, and
 * for a different reason.
 *
 * Each accessor is named for where the value goes, which keeps the session
 * out of the URL, and this is the other half of that: a type whose readers
 * say `forTheClient()` and `forTheOperatorWhoCouldNotReachIt()` makes building
 * a string out of an address for any other purpose read wrong at the call
 * site.
 *
 * **Whether the connection is encrypted is read off the scheme, here.**
 * The app must state it and must not imply protection it does not
 * have, and the answer is a property of the address rather than a judgement a
 * screen makes — so it is answered once, where the address is, instead of by
 * each screen that wants to show a padlock.
 *
 * Plain `http` is accepted rather than refused. A stack on a local network may
 * genuinely be reached that way, and refusing the address would tell an
 * operator their pairing code is broken when what is true is that their
 * connection is not private. That distinction is exactly what the rule exists
 * to keep, and it is lost if the value cannot be constructed.
 *
 * A scheme {@see Scheme} does not name is refused, which is the other side of
 * the same coin: `file://` is not an insecure stack, it is not a stack.
 */
final readonly class Address implements JsonSerializable
{
    /** How a name only the local network answers for ends. */
    private const string ONLY_THE_LOCAL_NETWORK_ANSWERS = '.local';

    /** One encrypted host and at most a port, and nothing else: a lowercase name, a dotted IPv4 address or a bracketed IPv6 one, then an optional closing slash. */
    private const string ONE_HOST_WRITTEN_PLAINLY = '~\Ahttps://(?<host>[a-z0-9.-]+|\[[0-9a-f:.]+\])(?<port>:[1-9][0-9]{0,4})?(?<closing>/?)\z~';

    /** One label of a name: letters, digits and inner hyphens, at most 63 long. */
    private const string A_LABEL = '~\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z~';

    /** What an internationalised label is written as, which reads as a different name from the one it dials. */
    private const string AN_ENCODED_LABEL = 'xn--';

    /** The highest port there is. */
    private const int HIGHEST_PORT = 65535;

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

    /**
     * The address a join link carries, refused unless it is one encrypted host and port written one way only.
     *
     * **One parse feeds what the person reads and what is dialled.** A link
     * anybody can write is shown to the person deciding whether to trust it, and
     * the address shown must be the one connected to. So the host and port are
     * read once, and the address held is rebuilt from them: no user before an
     * `@`, no backslash, no path, query or fragment, no percent-encoding, no
     * internationalised or encoded name, no trailing dot, no capital letters, no
     * number a resolver could read as an address other than the one it seems,
     * and no port outside the range or written with a leading zero.
     */
    public static function joinedAt(string $handed): self
    {
        if (preg_match(self::ONE_HOST_WRITTEN_PLAINLY, $handed, $found) !== 1 || ! self::isOneHost($found['host'])) {
            throw AddressIsUnreachable::notOneHostWrittenPlainly();
        }

        if ($found['port'] !== '' && (int) ltrim($found['port'], ':') > self::HIGHEST_PORT) {
            throw AddressIsUnreachable::notOneHostWrittenPlainly();
        }

        return new self(sprintf('https://%s%s', $found['host'], $found['port']), Scheme::Https);
    }

    /** The value the client dials, and nothing else. */
    public function forTheClient(): string
    {
        return $this->url;
    }

    /**
     * The address, for the operator's screen that says it was tried and not
     * reached, and nowhere else.
     *
     * The one place an address is shown: a stale or wrong one is what a reach
     * that met nothing often is, and the operator cannot see that without
     * seeing it. Never on a member's screen, never in a log, never in a
     * diagnostic report; `TheTriedAddressIsShownOnlyToTheOperatorTest` holds
     * every reader to that.
     */
    public function forTheOperatorWhoCouldNotReachIt(): string
    {
        return $this->url;
    }

    /**
     * The address a join link carries, for the person asked whether somebody in
     * their house sent it, and nowhere else.
     *
     * The one place a member sees an address: a link anybody can write names
     * the machine it would connect them to, and that is what they are deciding
     * to trust. `TheAddressALinkCarriesIsShownOnlyWhereItIsTrustedTest` holds
     * every reader to that.
     */
    public function forThePersonAskedToTrustIt(): string
    {
        return $this->url;
    }

    /**
     * Which kind of name it reaches its machine by: numeric, a `.local` name or
     * any other name, and nothing of the name itself.
     */
    public function howItIsWritten(): HowAnAddressIsWritten
    {
        $host = trim((string) parse_url($this->url, PHP_URL_HOST), '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return HowAnAddressIsWritten::Numeric;
        }

        return str_ends_with(mb_strtolower($host), self::ONLY_THE_LOCAL_NETWORK_ANSWERS)
            ? HowAnAddressIsWritten::LocalName
            : HowAnAddressIsWritten::Named;
    }

    /** The port it is dialled on, its own or its scheme's where it names none. */
    public function port(): int
    {
        $port = parse_url($this->url, PHP_URL_PORT);

        return is_int($port) ? $port : $this->scheme->standardPort();
    }

    /**
     * Whether what travels to this address is encrypted.
     *
     * The question, handed to the type that owns the answer. This
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

    /** Whether the host is a bracketed IPv6 address, a dotted IPv4 one, or a plain name. */
    private static function isOneHost(string $host): bool
    {
        if (str_starts_with($host, '[')) {
            return filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false || self::isAName($host);
    }

    /** Whether the host is a name whose labels are plain and whose last cannot be read as a number. */
    private static function isAName(string $host): bool
    {
        $labels = explode('.', $host);

        foreach ($labels as $label) {
            if (preg_match(self::A_LABEL, $label) !== 1 || str_starts_with($label, self::AN_ENCODED_LABEL)) {
                return false;
            }
        }

        return ! is_numeric($labels[count($labels) - 1][0]);
    }
}
