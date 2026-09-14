<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UpdateEnvelope;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Upkeep;
use Modules\Sdk\Internal\Endings;
use Modules\Sdk\Internal\Wire;

/**
 * The `update` envelope, read into what a screen can decide on.
 *
 * {@see Rosters} one endpoint over, and the same argument: the reading is a
 * separate thing from the port so that what a payload means is decided in one
 * place, and the adapter is left holding only the conversation.
 *
 * **A withdrawn release is read, not dropped.** The wire says when a release was
 * taken back rather than whether it was, and this turns the presence of that
 * date into the fact `N2-R16` is about. Dropping the release here instead would
 * leave a stack that is *running* a withdrawn one with nothing to say about it.
 */
final readonly class Standings
{
    /**
     * @param Envelope<mixed> $envelope the `update` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Upkeep
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw UpkeepIsUnreadable::missing(WireField::Data);
        }

        $changelog = self::changelog($data);

        $how = self::how($changelog);
        $waiting = self::waiting($changelog);
        $changing = self::changing($data);
        $went = Endings::in($data);
        $running = self::running($changelog);

        return $running instanceof Release
            ? Upkeep::runningOn($how, $running, $waiting, $changing, $went)
            : Upkeep::reported($how, $waiting, $changing, $went);
    }

    /**
     * The block the stack answers `N2-R15` and `N2-R16` in.
     *
     * Read once and handed down rather than found again by each reader, so
     * there is one answer to *where the changelog is* — and because the payload
     * carries a second `state` at the top, which says how the last applied
     * update finished. Reading that one for *current, pending or stale* is a
     * mistake that costs nothing at the point of writing and refuses every
     * stack with an update waiting, which is the only time this screen matters.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function changelog(array $data): array
    {
        if (! array_key_exists(WireField::Changelog->value, $data)) {
            throw UpkeepIsUnreadable::missing(WireField::Changelog);
        }

        $changelog = $data[WireField::Changelog->value];

        if (! is_array($changelog)) {
            throw UpkeepIsUnreadable::missing(WireField::Changelog);
        }

        return $changelog;
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return UpdateEnvelope::in($envelope)->data;
    }

    /**
     * Which of the three states the stack reported (`N2-R15`).
     *
     * @param  array<array-key, mixed>  $changelog
     */
    private static function how(array $changelog): HowCurrent
    {
        if (! array_key_exists(WireField::State->value, $changelog)) {
            throw UpkeepIsUnreadable::missing(WireField::State);
        }

        $said = $changelog[WireField::State->value];

        if (! is_string($said)) {
            throw UpkeepIsUnreadable::missing(WireField::State);
        }

        return HowCurrent::tryFrom($said) ?? throw UpkeepIsUnreadable::state($said);
    }

    /**
     * The release in use, where the stack named one.
     *
     * Absent rather than empty on a stack that has not determined it, which is
     * the distinction {@see Upkeep::running()} keeps: not looking and running
     * nothing are different answers.
     *
     * @param  array<array-key, mixed>  $changelog
     */
    private static function running(array $changelog): ?Release
    {
        if (! array_key_exists(WireField::Running->value, $changelog)) {
            return null;
        }

        $said = $changelog[WireField::Running->value];

        return is_array($said) ? self::release($said, 0) : null;
    }

    /**
     * Every release the changelog listed, in the order it listed them.
     *
     * @param  array<array-key, mixed>  $changelog
     */
    private static function waiting(array $changelog): Releases
    {
        if (! array_key_exists(WireField::Releases->value, $changelog)) {
            throw UpkeepIsUnreadable::missing(WireField::Releases);
        }

        $listed = $changelog[WireField::Releases->value];

        if (! is_array($listed)) {
            throw UpkeepIsUnreadable::missing(WireField::Releases);
        }

        $releases = [];
        $position = 0;

        foreach ($listed as $said) {
            if (! is_array($said)) {
                throw UpkeepIsUnreadable::release($position);
            }

            $releases[] = self::release($said, $position);
            ++$position;
        }

        return Releases::these(...$releases);
    }

    /**
     * The services taking an update would change.
     *
     * A change the stack has already refused is left out. It is not something
     * an update would do, and naming it in a confirmation would have somebody
     * agree to a service that was never going to move — which is the same
     * false promise `N2-R19` refuses about undoing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function changing(array $data): Services
    {
        if (! array_key_exists(WireField::Changes->value, $data)) {
            throw UpkeepIsUnreadable::missing(WireField::Changes);
        }

        $listed = $data[WireField::Changes->value];

        if (! is_array($listed)) {
            throw UpkeepIsUnreadable::missing(WireField::Changes);
        }

        $services = [];
        $position = 0;

        foreach ($listed as $said) {
            if (! is_array($said)) {
                throw UpkeepIsUnreadable::change($position);
            }

            if (self::refused($said, $position)) {
                ++$position;

                continue;
            }

            $services[] = ServiceId::called(self::named($said, $position));
            ++$position;
        }

        return Services::these(...$services);
    }

    /** @param array<array-key, mixed> $said */
    private static function named(array $said, int $position): string
    {
        if (! array_key_exists(WireField::Service->value, $said)) {
            throw UpkeepIsUnreadable::change($position);
        }

        $service = $said[WireField::Service->value];

        if (! is_string($service)) {
            throw UpkeepIsUnreadable::change($position);
        }

        return $service;
    }

    /**
     * Whether the stack has already said it will not make this change.
     *
     * Refused rather than defaulted, for the reason {@see noticeable()} gives:
     * the reassuring default here is *it will happen*, which is the direction
     * that puts a service in a confirmation it was never going to move for.
     *
     * @param array<array-key, mixed> $said
     */
    private static function refused(array $said, int $position): bool
    {
        if (! array_key_exists(WireField::Refused->value, $said)) {
            throw UpkeepIsUnreadable::change($position);
        }

        $refused = $said[WireField::Refused->value];

        if (! is_bool($refused)) {
            throw UpkeepIsUnreadable::change($position);
        }

        return $refused;
    }

    /**
     * One release, from the shape both the changelog and the running field use.
     *
     * @param array<array-key, mixed> $said
     */
    private static function release(array $said, int $position): Release
    {
        if (! array_key_exists(WireField::Version->value, $said)) {
            throw UpkeepIsUnreadable::release($position);
        }

        $version = $said[WireField::Version->value];

        if (! is_string($version)) {
            throw UpkeepIsUnreadable::release($position);
        }

        return Release::called($version, self::noticeable($said, $position), self::withdrawn($said));
    }

    /**
     * Whether somebody in the house would notice this release.
     *
     * Refused rather than defaulted when it is missing. The contract carries
     * this on every release, and a default here would be the reassuring one —
     * *nobody will notice* — which is the answer that quietly turns a decision
     * into a chore. The absent case is the stack's to explain, not this side's
     * to fill in.
     *
     * @param array<array-key, mixed> $said
     */
    private static function noticeable(array $said, int $position): bool
    {
        if (! array_key_exists(WireField::UserFacing->value, $said)) {
            throw UpkeepIsUnreadable::release($position);
        }

        $noticed = $said[WireField::UserFacing->value];

        if (! is_bool($noticed)) {
            throw UpkeepIsUnreadable::release($position);
        }

        return $noticed;
    }

    /**
     * Whether this release has been taken back.
     *
     * The wire carries *when*, so what makes a release withdrawn is that the
     * field is there at all and says something. A date this side cannot parse
     * is still a stack saying the release was taken back, and treating it as
     * standing would be the unsafe reading of an unreadable field.
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
