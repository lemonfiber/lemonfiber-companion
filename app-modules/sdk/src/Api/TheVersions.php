<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\VersionEnvelope;
use Modules\Kernel\Api\AGroupOfChanges;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\WhatRunsHere;
use Modules\Sdk\Api\Fields\VersionField;
use Modules\Sdk\Internal\Changelogs;
use Modules\Sdk\Internal\Wire;

/**
 * The `version` envelope, read into which versions a stack runs.
 *
 * {@see Standings}' sibling one endpoint over. The release record is read by
 * {@see Changelogs}, as it is there; this reads the three versions beside it,
 * and the running release's notes, which only this envelope is read for.
 *
 * Everything it refuses, it refuses as {@see VersionsAreUnreadable}, apart from
 * what {@see Changelogs} and the kernel values refuse in their own kinds.
 */
final readonly class TheVersions
{
    /**
     * @param Envelope<mixed> $envelope the `version` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatRunsHere
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw VersionsAreUnreadable::missing(WireField::Data);
        }

        $changelog = Changelogs::in($data);
        $lemonfiber = self::said($data, VersionField::Binary);
        $stack = self::said($data, WireField::Stack);
        $engine = self::engine($data);
        $notes = Changelogs::standing($changelog);
        $running = Changelogs::running($changelog);

        return $running instanceof Release
            ? WhatRunsHere::reported($lemonfiber, $stack, $engine, $notes, $running, ...self::changes($changelog))
            : WhatRunsHere::namingNoRelease($lemonfiber, $stack, $engine, $notes);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return VersionEnvelope::in($envelope)->data;
    }

    /**
     * A version the envelope always carries.
     *
     * @param array<array-key, mixed> $data
     */
    private static function said(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw VersionsAreUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * What the container engine reports, or nothing where the stack could not ask it.
     *
     * The contract marks it optional and says why: the stack answers without
     * it where the engine did not. Absent and not text are both that answer.
     *
     * @param array<array-key, mixed> $data
     */
    private static function engine(array $data): string
    {
        if (! array_key_exists(VersionField::Compose->value, $data)) {
            return '';
        }

        $said = $data[VersionField::Compose->value];

        return is_string($said) ? $said : '';
    }

    /**
     * The running release's notes, group by group, in the order they came.
     *
     * Read only where {@see Changelogs::running()} has already read a release
     * there, so the running field is a release by the time this looks at it.
     *
     * @param array<array-key, mixed> $changelog
     *
     * @return list<AGroupOfChanges>
     */
    private static function changes(array $changelog): array
    {
        $running = $changelog[WireField::Running->value];

        if (! is_array($running) || ! array_key_exists(VersionField::Groups->value, $running) || ! is_array($running[VersionField::Groups->value])) {
            throw VersionsAreUnreadable::missing(VersionField::Groups);
        }

        $read = [];
        $position = 0;

        foreach ($running[VersionField::Groups->value] as $group) {
            $read[] = self::group($group, $position);
            ++$position;
        }

        return $read;
    }

    /** One group of the notes: its title, and the summary of every entry under it. */
    private static function group(mixed $group, int $position): AGroupOfChanges
    {
        if (
            ! is_array($group)
            || ! array_key_exists(WireField::Title->value, $group)
            || ! array_key_exists(VersionField::Entries->value, $group)
            || ! is_string($group[WireField::Title->value])
            || ! is_array($group[VersionField::Entries->value])
        ) {
            throw VersionsAreUnreadable::group($position);
        }

        $summaries = [];

        foreach ($group[VersionField::Entries->value] as $entry) {
            if (! is_array($entry) || ! array_key_exists(WireField::Summary->value, $entry) || ! is_string($entry[WireField::Summary->value])) {
                throw VersionsAreUnreadable::group($position);
            }

            $summaries[] = $entry[WireField::Summary->value];
        }

        return AGroupOfChanges::titled($group[WireField::Title->value], ...$summaries);
    }
}
