<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

use function trim;

/**
 * What a stack named when it refused a support bundle, in its words.
 *
 * The problem's `detail`: the settings a bundle would have shown, the room it
 * needed, and, where it would have carried a credential out, the file on the
 * machine and the line the credential sits in. That last is exactly what the
 * operator needs to act on, and exactly what must not travel: a pointer to
 * where a key is kept is half of the key's way out. So this is drawn on the
 * screen that asked, and redacted the way {@see Address} is from every other
 * reader.
 *
 * The only accessor is named for where the value goes, for {@see Address::forTheClient()}'s
 * reason. A refusal that named nothing reads as blank there, and the screen
 * draws nothing for it.
 */
final readonly class WhatTheRefusalNamed implements JsonSerializable
{
    private function __construct(private string $said) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['said' => '(what a refusal named, hidden)'];
    }

    /**
     * What `serialize()` writes, which is nothing, for {@see MustNotLeaveThisProcess}'s reason.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::whatARefusalNamed();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::whatARefusalNamed();
    }

    /** What the stack named; only spaces is a refusal that named nothing. */
    public static function as(string $said): self
    {
        return new self(trim($said));
    }

    /** A refusal that named nothing. */
    public static function nothing(): self
    {
        return new self('');
    }

    /** The words, for the screen that asked, and blank where nothing was named. */
    public function forTheOperator(): string
    {
        return $this->said;
    }

    /** What `json_encode` writes. */
    public function jsonSerialize(): string
    {
        return '(what a refusal named, hidden)';
    }
}
