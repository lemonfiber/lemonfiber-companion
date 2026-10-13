<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One phone key a stack lists: its name, where it lives, when it was registered and last used, and whether it was revoked.
 *
 * **Only the public half is the stack's**, and the listing carries none of it but its name.
 */
final readonly class APhoneKey
{
    private function __construct(
        private APhoneKeysName $name,
        private WhereAPhoneKeyLives $lives,
        private AMomentAsWritten $registered,
        private AMomentAsWritten $used,
        private ?AMomentAsWritten $revoked,
    ) {}

    /** A phone key the stack lists as standing; `$used` is unreadable where it never proved a mint. */
    public static function standing(APhoneKeysName $name, WhereAPhoneKeyLives $lives, AMomentAsWritten $registered, AMomentAsWritten $used): self
    {
        return new self($name, $lives, $registered, $used, null);
    }

    /** A phone key the stack lists as revoked, and when. */
    public static function revoked(APhoneKeysName $name, WhereAPhoneKeyLives $lives, AMomentAsWritten $registered, AMomentAsWritten $used, AMomentAsWritten $when): self
    {
        return new self($name, $lives, $registered, $used, $when);
    }

    /** The name it was registered under, which revoking it names. */
    public function name(): APhoneKeysName
    {
        return $this->name;
    }

    public function lives(): WhereAPhoneKeyLives
    {
        return $this->lives;
    }

    public function registered(): AMomentAsWritten
    {
        return $this->registered;
    }

    /** When it last proved a mint; unreadable where it never has. */
    public function used(): AMomentAsWritten
    {
        return $this->used;
    }

    /** Whether it was revoked, so it proves nothing any more and revoking it again is not offered. */
    public function wasRevoked(): bool
    {
        return $this->revoked instanceof AMomentAsWritten;
    }

    /** When it was revoked; unreadable where it stands. */
    public function whenRevoked(): AMomentAsWritten
    {
        return $this->revoked ?? AMomentAsWritten::byTheStack('');
    }
}
