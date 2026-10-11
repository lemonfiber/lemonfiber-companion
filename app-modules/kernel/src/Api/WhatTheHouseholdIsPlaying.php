<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function implode;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * Every session the media server reports playing, in the order the core answered, or why it could not say.
 *
 * Unread is not nothing playing: where the core could not read the media
 * server, it holds what the core said instead and no sessions.
 *
 * @implements IteratorAggregate<int, ASessionPlaying>
 */
final readonly class WhatTheHouseholdIsPlaying implements IteratorAggregate
{
    /**
     * @param list<ASessionPlaying> $sessions
     * @param list<string>          $why
     */
    private function __construct(private array $sessions, private bool $read, private array $why) {}

    /** What the media server is playing; none at all is nothing playing. */
    public static function these(ASessionPlaying ...$sessions): self
    {
        return new self(array_values($sessions), read: true, why: []);
    }

    /** The core could not read the media server, and said why. */
    public static function unread(string ...$why): self
    {
        return new self([], read: false, why: array_values($why));
    }

    /** Whether the core could read the media server. */
    public function wasRead(): bool
    {
        return $this->read;
    }

    /** What the core said instead, where it could not read the media server; empty where it could. */
    public function whyNot(): string
    {
        return implode(' ', $this->why);
    }

    /** @return Traversable<int, ASessionPlaying> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->sessions);
    }
}
