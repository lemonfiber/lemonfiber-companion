<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Sdk\Api\ChangelogIsUnreadable;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * The `changelog` block, read into releases.
 *
 * One reader for the block wherever it arrives. The `update` and `version`
 * envelopes both carry it under `changelog`, in one shape: the release that is
 * running, and every release the stack's record holds, newest first. That list
 * is history up to the running build, not a list of updates waiting.
 *
 * **A withdrawn release is read, not dropped.** The wire says when a release
 * was taken back rather than whether it was, and this turns the presence of
 * that date into the fact. Dropping the release here would leave a stack that
 * is running a withdrawn one with nothing to say about it.
 *
 * Everything it refuses, it refuses as {@see ChangelogIsUnreadable}, so an
 * adapter catches one kind for the block whichever envelope carried it.
 */
final readonly class Changelogs
{
    /**
     * The block itself, out of an envelope's payload.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function in(array $data): array
    {
        if (! array_key_exists(UpdateField::Changelog->value, $data)) {
            throw ChangelogIsUnreadable::missing(UpdateField::Changelog);
        }

        $changelog = $data[UpdateField::Changelog->value];

        if (! is_array($changelog)) {
            throw ChangelogIsUnreadable::missing(UpdateField::Changelog);
        }

        return $changelog;
    }

    /**
     * The release that is running, where the stack named one.
     *
     * Absent or null where the stack has not determined it, which is an answer
     * and not a payload gone wrong: not having looked and running nothing are
     * different answers.
     *
     * @param  array<array-key, mixed>  $changelog
     */
    public static function running(array $changelog): ?Release
    {
        if (! array_key_exists(WireField::Running->value, $changelog)) {
            return null;
        }

        $said = $changelog[WireField::Running->value];

        if ($said === null) {
            return null;
        }

        if (! is_array($said)) {
            throw ChangelogIsUnreadable::running();
        }

        return self::release($said, ChangelogIsUnreadable::running());
    }

    /**
     * Every release the record holds, in the order it listed them.
     *
     * @param  array<array-key, mixed>  $changelog
     */
    public static function history(array $changelog): Releases
    {
        if (! array_key_exists(UpdateField::Releases->value, $changelog)) {
            throw ChangelogIsUnreadable::missing(UpdateField::Releases);
        }

        $listed = $changelog[UpdateField::Releases->value];

        if (! is_array($listed)) {
            throw ChangelogIsUnreadable::missing(UpdateField::Releases);
        }

        $releases = [];
        $position = 0;

        foreach ($listed as $said) {
            if (! is_array($said)) {
                throw ChangelogIsUnreadable::release($position);
            }

            $releases[] = self::release($said, ChangelogIsUnreadable::release($position));
            ++$position;
        }

        return Releases::these(...$releases);
    }

    /**
     * One release, from the shape both the running field and the list use.
     *
     * @param array<array-key, mixed> $said
     */
    private static function release(array $said, ChangelogIsUnreadable $unreadable): Release
    {
        if (! array_key_exists(UpdateField::Version->value, $said)) {
            throw $unreadable;
        }

        $version = $said[UpdateField::Version->value];

        // Blank as well as absent, because {@see Release::called()} refuses a
        // blank one with its own kind, which no adapter catches.
        if (! is_string($version) || trim($version) === '') {
            throw $unreadable;
        }

        return Release::called(
            $version,
            self::noticeable($said, $unreadable),
            self::withdrawn($said),
            self::delivers($said),
        );
    }

    /**
     * Whether somebody in the house would notice this release.
     *
     * Refused rather than defaulted when it is missing. The contract carries
     * it on every release, and the default would be the reassuring answer —
     * *nobody will notice*.
     *
     * @param array<array-key, mixed> $said
     */
    private static function noticeable(array $said, ChangelogIsUnreadable $unreadable): bool
    {
        if (! array_key_exists(UpdateField::UserFacing->value, $said)) {
            throw $unreadable;
        }

        $noticed = $said[UpdateField::UserFacing->value];

        if (! is_bool($noticed)) {
            throw $unreadable;
        }

        return $noticed;
    }

    /**
     * What the stack says this release delivers.
     *
     * Absent is an answer and is read as one: the contract marks the field
     * optional. A value that is not text is read the same way rather than
     * refused, because a number where prose belongs is a stack that has given
     * no prose.
     *
     * @param array<array-key, mixed> $said
     */
    private static function delivers(array $said): WhatAReleaseDelivers
    {
        if (! array_key_exists(UpdateField::Delivers->value, $said)) {
            return WhatAReleaseDelivers::saidNothing();
        }

        $prose = $said[UpdateField::Delivers->value];

        return is_string($prose)
            ? WhatAReleaseDelivers::said($prose)
            : WhatAReleaseDelivers::saidNothing();
    }

    /**
     * Whether this release has been taken back.
     *
     * The wire carries *when*, so what makes a release withdrawn is that the
     * field is there and says something. A date this side cannot parse is
     * still a stack saying the release was taken back.
     *
     * @param array<array-key, mixed> $said
     */
    private static function withdrawn(array $said): bool
    {
        if (! array_key_exists(WireField::Withdrawn->value, $said)) {
            return false;
        }

        return $said[WireField::Withdrawn->value] !== null;
    }
}
