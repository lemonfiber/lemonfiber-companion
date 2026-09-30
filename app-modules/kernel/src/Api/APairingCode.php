<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A pairing code the stack made, and what the operator is told beside it.
 *
 * The line another phone scans or types, the short form of the fingerprint a
 * person compares once it is typed, the moment it stops being good, and where
 * the phone reaches this machine with what is worth knowing about that
 * address. Every word is the stack's; the moment is the stack's too, and what
 * a clock on this phone reads at it is the screen's to say.
 *
 * **It is pairing material, so it never leaves this process.** It carries no
 * credential, and it still says where a machine is and which certificate it
 * presents. `serialize()` is refused and a dump says what it is and nothing it
 * says; it is held by the screen that asked for it and by nothing else.
 */
final readonly class APairingCode
{
    private function __construct(
        private APairingLine $line,
        private string $compare,
        private Instant $expires,
        private string $address,
        private string $caution,
    ) {}

    /**
     * What a dump shows, which is that it is a pairing code and nothing it says.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['code' => '(a pairing code, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aPairingLine();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aPairingLine();
    }

    /**
     * What the stack answered; a blank word it owes is refused.
     *
     * `caution` is empty where the address keeps working on its own, and
     * `expires` is the moment it stops being good.
     */
    public static function made(
        APairingLine $line,
        string $compare,
        Instant $expires,
        string $address,
        string $caution,
    ): self {
        foreach (['compare' => $compare, 'address' => $address] as $field => $said) {
            if (trim($said) === '') {
                throw PairingIsNotReadable::becauseItSaysNothingAbout($field);
            }
        }

        return new self($line, $compare, $expires, $address, $caution);
    }

    /** The line another phone scans or types. */
    public function line(): APairingLine
    {
        return $this->line;
    }

    /** The short form of the fingerprint, which the other phone shows once the line is typed. */
    public function compare(): string
    {
        return $this->compare;
    }

    /** The moment it stops being good. */
    public function expiresAt(): Instant
    {
        return $this->expires;
    }

    /** Where the other phone reaches this machine. */
    public function address(): string
    {
        return $this->address;
    }

    /** What is worth knowing about that address, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }

    /** Whether it has stopped being good by `$now`. */
    public function hasExpiredBy(Instant $now): bool
    {
        return ! $now->isBefore($this->expires);
    }
}
