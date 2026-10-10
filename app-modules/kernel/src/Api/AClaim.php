<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The claim an invitation's join link carries, which lets the person it names choose their own password.
 *
 * It never leaves this process: `serialize()` is refused and a dump says what
 * it is and not what it says. It is offered as often as a claim is tried,
 * since a claim the media server could not confirm is kept by the core and
 * tried again.
 */
final readonly class AClaim
{
    private function __construct(private string $token) {}

    /**
     * What a dump shows, which is that it is a claim and nothing it says.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['token' => '(a claim, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aClaim();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aClaim();
    }

    /** The claim a join link carried; an empty one is refused, since it claims nothing. */
    public static function carried(string $token): self
    {
        if ($token === '') {
            throw JoinLinkCannotBeUsed::because(WhyAJoinLinkCannotBeUsed::WithAParameterItCannotRead, WhatAJoinLinkSays::Claim->value);
        }

        return new self($token);
    }

    /** The token, for the one exchange that claims the invitation. */
    public function forTheExchange(): string
    {
        return $this->token;
    }
}
