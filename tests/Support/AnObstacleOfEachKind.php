<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;

/**
 * One obstacle met of every kind, each with facts where its kind carries them.
 *
 * For a test that walks every kind and needs each one met: a kind that carries
 * facts cannot be met without them, so they are supplied here once.
 */
final readonly class AnObstacleOfEachKind
{
    /** The stack answering one version past the one this app reads. */
    public const int A_NEWER_VERSION = 2;

    /** The version this app reads, as the facts above have it. */
    public const int THE_VERSION_READ = 1;

    /** This kind, met. */
    public static function met(KindOfObstacle $kind): Obstacle
    {
        return $kind === KindOfObstacle::VersionsDisagree
            ? Obstacle::versionsDisagree(TheVersionsSpoken::between(self::A_NEWER_VERSION, self::THE_VERSION_READ))
            : Obstacle::of($kind);
    }

    /** @return list<Obstacle> one of every kind, in the order the kinds are declared */
    public static function all(): array
    {
        return array_map(self::met(...), KindOfObstacle::cases());
    }
}
