<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\QueueEnvelope;
use Modules\Kernel\Api\ADownloadInTheQueue;
use Modules\Kernel\Api\AHealingAct;
use Modules\Kernel\Api\AHealingRun;
use Modules\Kernel\Api\AnActTaken;
use Modules\Kernel\Api\AnItemActedOn;
use Modules\Kernel\Api\AServiceHealingCouldNotReach;
use Modules\Kernel\Api\AWedgedItem;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatBecameOfAnAct;
use Modules\Kernel\Api\WhetherHealingActs;
use Modules\Kernel\Api\WhyItIsWedged;
use Modules\Sdk\Api\Fields\QueueField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `queue` envelope, as how the queue heals itself.
 *
 * The sibling of {@see WhatTheWatcherSaw}, written the same way.
 */
final readonly class WhatTheQueueSaw
{
    /**
     * Whether healing acts, the strikes and grace it acts after, every unhealthy item, what it could not reach, and every run.
     *
     * @param Envelope<mixed> $envelope the `queue` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): HowTheQueueHeals
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw QueueIsUnreadable::missing(WireField::Data);
        }

        $healing = Required::text($data, QueueField::Healing, QueueIsUnreadable::missing(QueueField::Healing));
        $queue = HowTheQueueHeals::switched(
            WhetherHealingActs::tryFrom($healing) ?? throw QueueIsUnreadable::word(QueueField::Healing, $healing, ...array_map(static fn(WhetherHealingActs $one): string => $one->value, WhetherHealingActs::cases())),
            Required::count($data, QueueField::StrikesNeeded, QueueIsUnreadable::missing(QueueField::StrikesNeeded)),
            HowLong::ofSeconds(Required::number($data, WireField::GraceS, QueueIsUnreadable::missing(WireField::GraceS))),
            Optional::instant($data, QueueField::ClassifiedAt, QueueIsUnreadable::missing(QueueField::ClassifiedAt)),
            Optional::text($data, QueueField::Mismatch, QueueIsUnreadable::missing(QueueField::Mismatch)),
        );
        $queue = Required::flag($data, WireField::Incomplete, QueueIsUnreadable::missing(WireField::Incomplete)) ? $queue->mayBeShort() : $queue;
        $queue = Required::flag($data, WireField::Rehearsed, QueueIsUnreadable::missing(WireField::Rehearsed)) ? $queue->rehearsed() : $queue;

        $items = [];

        foreach (self::rows($data, WireField::Items) as $item) {
            $items[] = self::wedged($item);
        }

        $unreachable = [];

        foreach (self::rows($data, QueueField::Unreachable) as $service) {
            $unreachable[] = self::unreached($service);
        }

        $runs = [];

        foreach (self::rows($data, QueueField::Runs) as $run) {
            $runs[] = self::run($run);
        }

        return $queue->finding(...$items)->notReaching(...$unreachable)->after(...$runs);
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return QueueEnvelope::in($envelope)->data;
    }

    /** One unhealthy item, with when it was added, its grace and what the next run would do, where each is said. */
    private static function wedged(mixed $row): AWedgedItem
    {
        $table = self::table($row, WireField::Items);
        $class = Required::text($table, QueueField::Classification, QueueIsUnreadable::missing(QueueField::Classification));
        $item = AWedgedItem::flagged(
            self::download($table),
            WhyItIsWedged::tryFrom($class) ?? throw QueueIsUnreadable::word(QueueField::Classification, $class, ...array_map(static fn(WhyItIsWedged $one): string => $one->value, WhyItIsWedged::cases())),
            Required::text($table, WireField::Why, QueueIsUnreadable::missing(WireField::Why)),
            Required::count($table, QueueField::Strikes, QueueIsUnreadable::missing(QueueField::Strikes)),
        );
        $next = [];

        foreach (self::rows($table, WireField::Next) as $act) {
            $next[] = self::act($act, WireField::Next);
        }

        return $item->added(Optional::instant($table, QueueField::AddedAt, QueueIsUnreadable::missing(QueueField::AddedAt)))
            ->leftAloneUntil(Optional::instant($table, QueueField::InGraceUntil, QueueIsUnreadable::missing(QueueField::InGraceUntil)))
            ->nextWould(...$next);
    }

    /** One run, with every item it acted on. */
    private static function run(mixed $row): AHealingRun
    {
        $table = self::table($row, QueueField::Runs);
        $at = Instant::atEpochSeconds(Required::number($table, WireField::At, QueueIsUnreadable::missing(WireField::At)));
        $items = [];

        foreach (self::rows($table, WireField::Items) as $item) {
            $items[] = self::actedOn($item);
        }

        return Required::flag($table, WireField::Rehearsed, QueueIsUnreadable::missing(WireField::Rehearsed)) ? AHealingRun::rehearsedAt($at, ...$items) : AHealingRun::ranAt($at, ...$items);
    }

    /** One item a run acted on, with each act in the order it was taken. */
    private static function actedOn(mixed $row): AnItemActedOn
    {
        $table = self::table($row, WireField::Items);
        $acts = [];

        foreach (self::rows($table, QueueField::Acts) as $act) {
            $acts[] = self::taken($act);
        }

        return AnItemActedOn::because(self::download($table), Required::text($table, WireField::Why, QueueIsUnreadable::missing(WireField::Why)), ...$acts);
    }

    /** One act as a run took it, with what was done in its place where something weaker was. */
    private static function taken(mixed $row): AnActTaken
    {
        $table = self::table($row, QueueField::Acts);
        $outcome = Required::text($table, WireField::Outcome, QueueIsUnreadable::missing(WireField::Outcome));
        $taken = AnActTaken::took(
            self::act(Required::text($table, QueueField::Act, QueueIsUnreadable::missing(QueueField::Act)), QueueField::Act),
            WhatBecameOfAnAct::tryFrom($outcome) ?? throw QueueIsUnreadable::word(WireField::Outcome, $outcome, ...array_map(static fn(WhatBecameOfAnAct $one): string => $one->value, WhatBecameOfAnAct::cases())),
            Optional::text($table, WireField::Why, QueueIsUnreadable::missing(WireField::Why)),
        );
        $instead = Optional::text($table, WireField::Instead, QueueIsUnreadable::missing(WireField::Instead));

        return $instead === '' ? $taken : $taken->inPlaceOf(self::act($instead, WireField::Instead));
    }

    /** A curator or download client the latest pass could not reach, and why. */
    private static function unreached(mixed $row): AServiceHealingCouldNotReach
    {
        $table = self::table($row, QueueField::Unreachable);

        return AServiceHealingCouldNotReach::because(
            ServiceId::called(Required::text($table, WireField::Service, QueueIsUnreadable::missing(WireField::Service))),
            Required::text($table, WireField::Why, QueueIsUnreadable::missing(WireField::Why)),
        );
    }

    /**
     * The download an item names: who holds it, its release and the title it is for.
     *
     * @param array<mixed> $table
     */
    private static function download(array $table): ADownloadInTheQueue
    {
        return ADownloadInTheQueue::heldBy(
            ServiceId::called(Required::text($table, WireField::Service, QueueIsUnreadable::missing(WireField::Service))),
            Required::text($table, WireField::Release, QueueIsUnreadable::missing(WireField::Release)),
            Optional::text($table, WireField::Title, QueueIsUnreadable::missing(WireField::Title)),
        );
    }

    /** One of the three acts, by its word. */
    private static function act(mixed $said, NamesAWireField $field): AHealingAct
    {
        if (! Required::isText($said)) {
            throw QueueIsUnreadable::missing($field);
        }

        return AHealingAct::tryFrom($said) ?? throw QueueIsUnreadable::word($field, $said, ...array_map(static fn(AHealingAct $one): string => $one->value, AHealingAct::cases()));
    }

    /**
     * A row that must be an object.
     *
     * @return array<mixed>
     */
    private static function table(mixed $row, NamesAWireField $field): array
    {
        return is_array($row) ? $row : throw QueueIsUnreadable::missing($field);
    }

    /**
     * A list a payload may leave out, empty where it does.
     *
     * @param  array<mixed> $table
     * @return list<mixed>
     */
    private static function rows(array $table, NamesAWireField $field): array
    {
        return Optional::rows($table, $field, QueueIsUnreadable::missing($field));
    }
}
