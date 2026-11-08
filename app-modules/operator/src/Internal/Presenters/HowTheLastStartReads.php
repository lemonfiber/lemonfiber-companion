<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Operator\Internal\ViewModels\ACheckAfterAStartAsShown;
use Modules\Operator\Internal\ViewModels\APortAfterAStartAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheLastStartAsShown;
use Modules\Operator\Internal\ViewModels\WhatFailingCameTo;

/**
 * What asking a stack about its last start produces, as screen fields.
 *
 * `F2` — data in, view model out. A check is drawn as the one after the latest
 * restart only where the stack kept one against a start it named; otherwise
 * the newest check kept is said as an earlier one.
 */
final readonly class HowTheLastStartReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): TheLastStartAsShown
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /** It did not answer, and this is what the operator met. */
    public function met(Obstacle $why): TheLastStartAsShown
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack said what its last start came to. */
    public function this(WhatTheLastStartCameTo $last, Instant $now): TheLastStartAsShown
    {
        $sinceIt = $last->sinceIt()->either(
            kept: static fn(ACheckAfterAStart $check): ACheckAfterAStartAsShown => self::check($check, 'restarts.checked', $now),
            none: static fn(): ACheckAfterAStartAsShown => self::noCheck(),
        );
        $earlier = $last->newest()->either(
            kept: static fn(ACheckAfterAStart $check): ACheckAfterAStartAsShown => self::check($check, 'restarts.earlier', $now),
            none: static fn(): ACheckAfterAStartAsShown => self::noCheck(),
        );
        $failing = $last->failing()->either(
            failing: static fn(int $starts, WhenItSays $since): WhatFailingCameTo => new WhatFailingCameTo($since->isSaid() ? 'restarts.failing' : 'restarts.failing_undated', $starts, self::ago($since, $now)),
            notFailing: static fn(): WhatFailingCameTo => new WhatFailingCameTo('', 0, AgoAsShown::saysNothing()),
        );
        $started = $last->started();

        return new TheLastStartAsShown(
            went: HowTheReadingWent::itCameBack(),
            startSaid: match (true) {
                ! $started->isSaid() => 'restarts.start_unknown',
                $last->sinceIt()->isKept() => 'restarts.started',
                default => 'restarts.not_run',
            },
            startedAgo: self::ago($started, $now),
            check: $last->sinceIt()->isKept() ? $sinceIt : $earlier,
            declined: $last->declined(),
            failing: $failing,
            nothingKept: ! $started->isSaid() && ! $last->newest()->isKept() && $failing->starts === 0,
        );
    }

    private function nothing(HowTheReadingWent $went): TheLastStartAsShown
    {
        return new TheLastStartAsShown($went, '', AgoAsShown::saysNothing(), self::noCheck(), '', new WhatFailingCameTo('', 0, AgoAsShown::saysNothing()), nothingKept: false);
    }

    private static function noCheck(): ACheckAfterAStartAsShown
    {
        return new ACheckAfterAStartAsShown('', AgoAsShown::saysNothing(), '', [], '', '', new APortAfterAStartAsShown('', 0, 0), notEveryClient: false, said: '');
    }

    private static function check(ACheckAfterAStart $check, string $whenSaid, Instant $now): ACheckAfterAStartAsShown
    {
        $notBack = [];

        foreach ($check->notBack() as $service) {
            $notBack[] = $service;
        }

        return new ACheckAfterAStartAsShown(
            whenSaid: $whenSaid,
            ago: self::ago(WhenItSays::at($check->at()), $now),
            endedSaid: $check->ended()->saidOnTheScreen(),
            notBack: $notBack,
            tunnelSaid: $check->tunnel()->came()->saidOnTheScreen(),
            tunnelWords: $check->tunnel()->said(),
            port: self::port($check->port()),
            notEveryClient: ! $check->port()->everyClientTakesIt(),
            said: $check->said(),
        );
    }

    private static function port(ThePortAfterAStart $port): APortAfterAStartAsShown
    {
        return $port->either(
            same: static fn(int $is): APortAfterAStartAsShown => new APortAfterAStartAsShown('restarts.port.same', $is, $is),
            moved: static fn(int $was, int $is): APortAfterAStartAsShown => new APortAfterAStartAsShown('restarts.port.moved', $was, $is),
            now: static fn(int $is): APortAfterAStartAsShown => new APortAfterAStartAsShown('restarts.port.now', 0, $is),
            unsaid: static fn(): APortAfterAStartAsShown => new APortAfterAStartAsShown('restarts.port.unknown', 0, 0),
            notForwarded: static fn(): APortAfterAStartAsShown => new APortAfterAStartAsShown('', 0, 0),
        );
    }

    private static function ago(WhenItSays $when, Instant $now): AgoAsShown
    {
        return $when->either(
            at: static fn(Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
            unsaid: static fn(): AgoAsShown => AgoAsShown::saysNothing(),
        );
    }
}
