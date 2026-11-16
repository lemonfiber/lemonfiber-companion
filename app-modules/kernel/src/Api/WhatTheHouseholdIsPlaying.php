<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
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
     */
    private function __construct(private array $sessions, private bool $read, private TheObstaclesNamed $why) {}

    /** What the media server is playing; none at all is nothing playing. */
    public static function these(ASessionPlaying ...$sessions): self
    {
        return new self(array_values($sessions), read: true, why: TheObstaclesNamed::of());
    }

    /** The core could not read the media server, and named what stood in the way. */
    public static function unread(AnObstacleNamed ...$why): self
    {
        return new self([], read: false, why: TheObstaclesNamed::of(...$why));
    }

    /** Whether the core could read the media server. */
    public function wasRead(): bool
    {
        return $this->read;
    }

    /** What stood in the way, where the core could not read the media server; none where it could. */
    public function whatStoodInTheWay(): TheObstaclesNamed
    {
        return $this->why;
    }

    /** @return Traversable<int, ASessionPlaying> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->sessions);
    }
}
