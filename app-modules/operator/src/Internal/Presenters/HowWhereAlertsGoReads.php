<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\ADelivery;
use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\ARetry;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Operator\Internal\ViewModels\ADeliveryAsShown;
use Modules\Operator\Internal\ViewModels\AHostAsShown;
use Modules\Operator\Internal\ViewModels\AnAlertBackEndAsShown;
use Modules\Operator\Internal\ViewModels\AnAlertLineAsShown;
use Modules\Operator\Internal\ViewModels\ARetryAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhereAlertsGoTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Stacks\Api\AStacksScreen;

/** Where a machine's alerts go, flattened for Where alerts go and for one back-end's own page. */
final readonly class HowWhereAlertsGoReads
{
    private const string CHOSEN_BY_YOU = 'alerts.chosen.you';

    private const string CHOSEN_BY_A_MEMBER = 'alerts.chosen.member';

    private const string NEVER_DELIVERED = 'alerts.proof.unproven';

    private const string LAST_PROVEN = 'alerts.proof.proven';

    private const string HELD = 'alerts.proof.held';

    private const string CONFIRMED = 'alerts.ended.confirmed';

    private const string SENT = 'alerts.ended.sent';

    private const string FAILED = 'alerts.ended.failed';

    private const string NOTHING_YET = 'alerts.ended.none';

    private const string NEXT_ATTEMPT = 'alerts.retry';

    public function __construct(private WhereAStackIs $where, private Instant $now) {}

    public static function signedOut(): WhereAlertsGoTurnedOutToBe
    {
        return new WhereAlertsGoTurnedOutToBe(HowTheReadingWent::theSessionEnded(), notSetUp: false, stateSaid: '', stateTone: Tone::Unknown->value, backEnds: [], unread: []);
    }

    public static function met(Obstacle $why): WhereAlertsGoTurnedOutToBe
    {
        return new WhereAlertsGoTurnedOutToBe(HowTheReadingWent::somethingStopped($why), notSetUp: false, stateSaid: '', stateTone: Tone::Unknown->value, backEnds: [], unread: []);
    }

    /** Every back-end, in the core's order, and the unread apart. */
    public function all(WhereAlertsGo $read): WhereAlertsGoTurnedOutToBe
    {
        $backEnds = [];

        foreach ($read->backEnds as $backEnd) {
            $backEnds[] = $this->backEnd($backEnd);
        }

        return $this->turnedOut($read, $backEnds);
    }

    /** The one back-end a page is about, or none where the core no longer lists it. */
    public function one(WhereAlertsGo $read, AlertBackEndId $id): WhereAlertsGoTurnedOutToBe
    {
        $backEnds = [];

        foreach ($read->backEnds as $backEnd) {
            if ($backEnd->id()->isTheSameAs($id)) {
                $backEnds[] = $this->backEnd($backEnd);
            }
        }

        return $this->turnedOut($read, $backEnds);
    }

    /** @param list<AnAlertBackEndAsShown> $backEnds */
    private function turnedOut(WhereAlertsGo $read, array $backEnds): WhereAlertsGoTurnedOutToBe
    {
        $unread = [];

        foreach ($read->unread as $one) {
            $unread[$one->id->named()] = $one->because;
        }

        return new WhereAlertsGoTurnedOutToBe(
            HowTheReadingWent::itCameBack(),
            notSetUp: $read->isNotSetUp(),
            stateSaid: $read->stands->saidOnTheScreen(),
            stateTone: self::tone($read->stands)->value,
            backEnds: $backEnds,
            unread: $unread,
        );
    }

    private static function tone(HowDeliveryStands $stands): Tone
    {
        return match ($stands) {
            HowDeliveryStands::Ready => Tone::Fine,
            HowDeliveryStands::Degraded => Tone::Attention,
            HowDeliveryStands::Unreachable => Tone::Trouble,
            HowDeliveryStands::Unconfigured => Tone::Quiet,
        };
    }

    private function backEnd(AnAlertBackEnd $backEnd): AnAlertBackEndAsShown
    {
        $failures = [];

        foreach ($backEnd->deliveries()->failures() as $failed) {
            $failures[] = $this->delivery($failed);
        }

        return new AnAlertBackEndAsShown(
            id: $backEnd->id()->named(),
            goes: $this->where->about(AStacksScreen::AlertBackEnd, $backEnd->id()),
            kindSaid: $backEnd->kind()->saidOnTheScreen(),
            where: $backEnd->where(),
            chosen: $backEnd->chosenBy()->either(
                operator: static fn(): AnAlertLineAsShown => new AnAlertLineAsShown(self::CHOSEN_BY_YOU, '', AgoAsShown::saysNothing()),
                member: static fn(string $name): AnAlertLineAsShown => new AnAlertLineAsShown(self::CHOSEN_BY_A_MEMBER, $name, AgoAsShown::saysNothing()),
            ),
            hosted: $backEnd->hosted()->either(
                notHosted: static fn(): AHostAsShown => new AHostAsShown('', []),
                hosted: static fn(string $host, array $leaves): AHostAsShown => new AHostAsShown($host, $leaves),
            ),
            proof: $backEnd->proof()->either(
                unproven: static fn(): AnAlertLineAsShown => new AnAlertLineAsShown(self::NEVER_DELIVERED, '', AgoAsShown::saysNothing()),
                proven: fn(Instant $at): AnAlertLineAsShown => new AnAlertLineAsShown(self::LAST_PROVEN, '', AgoAsShown::from(HowLongAgo::since($at, $this->now), $at, $this->now)),
                held: static fn(string $why): AnAlertLineAsShown => new AnAlertLineAsShown(self::HELD, $why, AgoAsShown::saysNothing()),
            ),
            last: $backEnd->deliveries()->last(
                delivered: $this->delivery(...),
                none: static fn(): ADeliveryAsShown => new ADeliveryAsShown(new AnAlertLineAsShown(self::NOTHING_YET, '', AgoAsShown::saysNothing()), ''),
            ),
            failures: $failures,
            retry: $backEnd->deliveries()->retry(
                waits: fn(ARetry $retry): ARetryAsShown => new ARetryAsShown(self::NEXT_ATTEMPT, AgoAsShown::until($retry->next, $this->now), $retry->attempt, $retry->of),
                none: static fn(): ARetryAsShown => new ARetryAsShown('', AgoAsShown::saysNothing(), 0, 0),
            ),
        );
    }

    private function delivery(ADelivery $delivery): ADeliveryAsShown
    {
        $ago = AgoAsShown::from(HowLongAgo::since($delivery->at, $this->now), $delivery->at, $this->now);

        return new ADeliveryAsShown(
            ended: $delivery->ended->either(
                confirmed: static fn(): AnAlertLineAsShown => new AnAlertLineAsShown(self::CONFIRMED, '', $ago),
                sent: static fn(): AnAlertLineAsShown => new AnAlertLineAsShown(self::SENT, '', $ago),
                failed: static fn(string $reason): AnAlertLineAsShown => new AnAlertLineAsShown(self::FAILED, $reason, $ago),
            ),
            kindSaid: $delivery->kind->saidOnTheScreen(),
        );
    }
}
