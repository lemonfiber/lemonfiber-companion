<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

use function preg_match;

/**
 * What lets this device play a member's titles through the household's door.
 *
 * The core authorises it on the member's own media-server account and answers
 * it once; it keeps no copy, so this device's secure store is the only place it
 * is. It is sent to the door as `Authorization: Bearer <grant>`, and nowhere
 * else: never in an address, never in a log.
 *
 * **Thirty-two lowercase hexadecimal digits, as the media server issues it.**
 * The door accepts that form and nothing else, so a grant of any other shape
 * is refused here rather than carried to a door that would refuse it there.
 *
 * Like {@see Session}, it will not be serialised or printed: a grant written
 * into a cache, a queued payload or a crash report is a grant somebody else
 * can play with.
 */
final readonly class AGrant implements JsonSerializable
{
    /** What the door accepts: thirty-two lowercase hexadecimal digits. */
    private const string WHAT_THE_DOOR_TAKES = '/\A[0-9a-f]{32}\z/';

    /** What a grant is printed or encoded as. */
    private const string HIDDEN = '(a grant, hidden)';

    private function __construct(private string $token, private Instant $lapsesAt) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['token' => self::HIDDEN];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aGrant();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aGrant();
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return self::HIDDEN;
    }

    /** The grant the core answered, and when it lapses. */
    public static function of(string $token, Instant $lapsesAt): self
    {
        if (preg_match(self::WHAT_THE_DOOR_TAKES, $token) !== 1) {
            throw GrantIsUnfit::fromTheStack();
        }

        return new self($token, $lapsesAt);
    }

    /** The grant as the door is handed it, and as the secure store keeps it. */
    public function forTheDoor(): string
    {
        return $this->token;
    }

    /** When it lapses, as the core last said. */
    public function lapsesAt(): Instant
    {
        return $this->lapsesAt;
    }

    /** Whether it had lapsed by a moment. */
    public function hasLapsedBy(Instant $now): bool
    {
        return ! $now->isBefore($this->lapsesAt);
    }
}
