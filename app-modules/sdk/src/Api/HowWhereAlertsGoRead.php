<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\NotifyEnvelope;
use Modules\Kernel\Api\ADelivery;
use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AlertBackEnds;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\AnUnreadBackEnd;
use Modules\Kernel\Api\ARetry;
use Modules\Kernel\Api\FailedDeliveries;
use Modules\Kernel\Api\HowADeliveryEnded;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\HowFarItIsProven;
use Modules\Kernel\Api\HowItsDeliveriesWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\UnreadBackEnds;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Kernel\Api\WhereItIsHosted;
use Modules\Kernel\Api\WhoChoseIt;
use Modules\Sdk\Api\Fields\NotifyField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatTheNotifyWordsAre;
use Modules\Sdk\Internal\Wire;

/** The `notify` envelope, read field by field into where a machine's alerts go. */
final readonly class HowWhereAlertsGoRead
{
    /** @param Envelope<mixed> $envelope the `notify` envelope, as the client returned it */
    public static function in(Envelope $envelope): WhereAlertsGo
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw WhereAlertsGoIsUnreadable::missing(WireField::Data->value);
        }

        $state = Required::text($data, WireField::State, WhereAlertsGoIsUnreadable::missing(WireField::State->value));

        return WhereAlertsGo::read(
            HowDeliveryStands::tryFrom($state) ?? throw WhereAlertsGoIsUnreadable::missing(WireField::State->value),
            AlertBackEnds::these(...self::backEnds($data)),
            UnreadBackEnds::these(...self::unread($data)),
        );
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return NotifyEnvelope::in($envelope)->data;
    }

    /**
     * @param  array<mixed> $data
     * @return list<AnAlertBackEnd>
     */
    private static function backEnds(array $data): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(Required::rows($data, NotifyField::BackEnds, WhereAlertsGoIsUnreadable::missing(NotifyField::BackEnds->value))) as $position => $row) {
            $found[] = self::backEnd($row, APath::beneath(NotifyField::BackEnds->value, $position));
        }

        return $found;
    }

    /**
     * @param  array<mixed> $data
     * @return list<AnUnreadBackEnd>
     */
    private static function unread(array $data): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Unread, WhereAlertsGoIsUnreadable::missing(WireField::Unread->value))) as $position => $row) {
            $where = APath::beneath(WireField::Unread->value, $position);
            $table = self::table($row, $where);

            $found[] = AnUnreadBackEnd::because(
                AlertBackEndId::called(self::text($table, NotifyField::BackEnd, $where)),
                self::text($table, WireField::Because, $where),
            );
        }

        return $found;
    }

    private static function backEnd(mixed $row, string $where): AnAlertBackEnd
    {
        $table = self::table($row, $where);
        $kind = self::text($table, WireField::Kind, $where);

        return AnAlertBackEnd::configured(
            AlertBackEndId::called(self::text($table, WireField::Id, $where)),
            KindOfAlertBackEnd::tryFrom($kind) ?? throw WhereAlertsGoIsUnreadable::missing(APath::beneath($where, WireField::Kind->value)),
            self::text($table, NotifyField::Where, $where),
            self::chosenBy($table, APath::beneath($where, NotifyField::ChosenBy->value)),
            self::hosted($table, APath::beneath($where, NotifyField::Hosted->value)),
            self::proof($table, APath::beneath($where, WireField::Proof->value)),
            self::deliveries($table, $where),
        );
    }

    /** @param array<mixed> $table */
    private static function chosenBy(array $table, string $where): WhoChoseIt
    {
        $said = self::optional($table, NotifyField::ChosenBy);

        if ($said === WhatTheNotifyWordsAre::Operator->value) {
            return WhoChoseIt::theOperator();
        }

        return WhoChoseIt::aMember(self::text(self::table($said, $where), WireField::Member, $where));
    }

    /** @param array<mixed> $table */
    private static function hosted(array $table, string $where): WhereItIsHosted
    {
        $said = self::optional($table, NotifyField::Hosted);

        if ($said === null) {
            return WhereItIsHosted::notHosted();
        }

        $hosted = self::table($said, $where);
        $leaves = [];

        foreach (InTheOrderSent::rows(Required::rows($hosted, WireField::Leaves, WhereAlertsGoIsUnreadable::missing(APath::beneath($where, WireField::Leaves->value)))) as $position => $left) {
            $leaves[] = is_string($left) ? $left : throw WhereAlertsGoIsUnreadable::missing(APath::beneath(APath::beneath($where, WireField::Leaves->value), $position));
        }

        return WhereItIsHosted::at(self::text($hosted, NotifyField::Host, $where), ...$leaves);
    }

    /** @param array<mixed> $table */
    private static function proof(array $table, string $where): HowFarItIsProven
    {
        $said = self::optional($table, WireField::Proof);

        if ($said === WhatTheNotifyWordsAre::Unproven->value) {
            return HowFarItIsProven::unproven();
        }

        $proof = self::table($said, $where);

        return match (true) {
            is_int(self::optional($proof, NotifyField::Proven)) => HowFarItIsProven::provenAt(Instant::atEpochSeconds(self::number($proof, NotifyField::Proven, $where))),
            default => HowFarItIsProven::held(self::text($proof, WireField::Held, $where)),
        };
    }

    /** @param array<mixed> $table */
    private static function deliveries(array $table, string $where): HowItsDeliveriesWent
    {
        $last = self::optional($table, NotifyField::Last);
        $went = $last === null
            ? HowItsDeliveriesWent::nothingYet()
            : HowItsDeliveriesWent::lastly(self::delivery($last, APath::beneath($where, NotifyField::Last->value)));

        $failures = [];

        foreach (InTheOrderSent::rows(Required::rows($table, NotifyField::Failures, WhereAlertsGoIsUnreadable::missing(APath::beneath($where, NotifyField::Failures->value)))) as $position => $row) {
            $failures[] = self::delivery($row, APath::beneath(APath::beneath($where, NotifyField::Failures->value), $position));
        }

        $went = $went->failedSince(FailedDeliveries::these(...$failures));
        $retry = self::optional($table, NotifyField::Retry);

        return $retry === null ? $went : $went->retrying(self::retry($retry, APath::beneath($where, NotifyField::Retry->value)));
    }

    private static function delivery(mixed $row, string $where): ADelivery
    {
        $table = self::table($row, $where);
        $kind = self::text($table, WireField::Kind, $where);

        return new ADelivery(
            Instant::atEpochSeconds(self::number($table, WireField::At, $where)),
            KindOfNotification::tryFrom($kind) ?? throw WhereAlertsGoIsUnreadable::missing(APath::beneath($where, WireField::Kind->value)),
            self::outcome($table, APath::beneath($where, WireField::Outcome->value)),
        );
    }

    /** @param array<mixed> $table */
    private static function outcome(array $table, string $where): HowADeliveryEnded
    {
        $said = self::optional($table, WireField::Outcome);

        return match ($said) {
            WhatTheNotifyWordsAre::Confirmed->value => HowADeliveryEnded::confirmed(),
            WhatTheNotifyWordsAre::Sent->value => HowADeliveryEnded::sent(),
            default => HowADeliveryEnded::failed(self::text(self::table($said, $where), NotifyField::Failed, $where)),
        };
    }

    private static function retry(mixed $said, string $where): ARetry
    {
        $table = self::table($said, $where);

        return new ARetry(
            self::number($table, NotifyField::Attempt, $where),
            self::number($table, WireField::Of, $where),
            Instant::atEpochSeconds(self::number($table, WireField::Next, $where)),
        );
    }

    /** @param array<mixed> $table */
    private static function optional(array $table, NamesAWireField $field): mixed
    {
        return array_key_exists($field->value, $table) ? $table[$field->value] : null;
    }

    /** @return array<mixed> */
    private static function table(mixed $said, string $where): array
    {
        return is_array($said) ? $said : throw WhereAlertsGoIsUnreadable::missing($where);
    }

    /** @param array<mixed> $table */
    private static function text(array $table, NamesAWireField $field, string $where): string
    {
        return Required::text($table, $field, WhereAlertsGoIsUnreadable::missing(APath::beneath($where, $field->value)));
    }

    /** @param array<mixed> $table */
    private static function number(array $table, NamesAWireField $field, string $where): int
    {
        return Required::number($table, $field, WhereAlertsGoIsUnreadable::missing(APath::beneath($where, $field->value)));
    }
}
