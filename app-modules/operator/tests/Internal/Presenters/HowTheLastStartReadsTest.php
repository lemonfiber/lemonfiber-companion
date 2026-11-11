<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ACheckAfterAStart;
use Modules\Kernel\Api\HowLongItHasFailed;
use Modules\Kernel\Api\HowTheCheckAfterAStartEnded;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCheckKept;
use Modules\Kernel\Api\ThePortAfterAStart;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheTunnelAfterAStart;
use Modules\Kernel\Api\WhatTheLastStartCameTo;
use Modules\Kernel\Api\WhenItSays;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheLastStartReads;
use Modules\Operator\Internal\ViewModels\ACheckAfterAStartAsShown;
use Modules\Operator\Internal\ViewModels\APortAfterAStartAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheLastStartAsShown;
use Modules\Operator\Internal\ViewModels\WhatFailingCameTo;

/** A check after a start that passed, at this moment, with this port. */
function aCheckWithThePort(ThePortAfterAStart $port): ACheckAfterAStart
{
    return ACheckAfterAStart::ran(Instant::atEpochSeconds(100), HowTheCheckAfterAStartEnded::Passed, TheServicesNamed::of(), TheTunnelAfterAStart::held(), $port, '');
}

/** The check a page draws where there is none. */
function noCheckDrawn(): ACheckAfterAStartAsShown
{
    return new ACheckAfterAStartAsShown('', AgoAsShown::saysNothing(), '', [], '', '', new APortAfterAStartAsShown('', 0, 0), notEveryClient: false, said: '');
}

it('draws nothing but what stopped it where the stack was not read', function (TheLastStartAsShown $read, HowTheReadingWent $went): void {
    expect($read)->toEqual(new TheLastStartAsShown($went, '', AgoAsShown::saysNothing(), noCheckDrawn(), '', new WhatFailingCameTo('', 0, AgoAsShown::saysNothing()), nothingKept: false));
})->with([
    'signed out' => [fn(): TheLastStartAsShown => new HowTheLastStartReads()->signedOut(), HowTheReadingWent::theSessionEnded()],
    'not answering' => [fn(): TheLastStartAsShown => new HowTheLastStartReads()->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), HowTheReadingWent::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
]);

it('draws no check, no count and no time where the stack kept no check and is not failing', function (): void {
    $read = new HowTheLastStartReads()->this(WhatTheLastStartCameTo::said(WhenItSays::at(Instant::atEpochSeconds(50)), TheCheckKept::none(), TheCheckKept::none(), '', HowLongItHasFailed::notFailing()), Instant::atEpochSeconds(200));

    expect($read->check)->toEqual(noCheckDrawn())
        ->and($read->failing)->toEqual(new WhatFailingCameTo('', 0, AgoAsShown::saysNothing()));
});

it('says the stack keeps nothing of a start only where it names no start, keeps no check and is not failing', function (WhenItSays $started, TheCheckKept $newest, HowLongItHasFailed $failing, bool $nothingKept): void {
    $read = new HowTheLastStartReads()->this(WhatTheLastStartCameTo::said($started, TheCheckKept::none(), $newest, '', $failing), Instant::atEpochSeconds(200));

    expect($read->nothingKept)->toBe($nothingKept);
})->with([
    'nothing at all' => [WhenItSays::unsaid(), TheCheckKept::none(), HowLongItHasFailed::notFailing(), true],
    'a start named' => [WhenItSays::at(Instant::atEpochSeconds(50)), TheCheckKept::none(), HowLongItHasFailed::notFailing(), false],
    'a check kept' => [WhenItSays::unsaid(), fn(): TheCheckKept => TheCheckKept::this(aCheckWithThePort(ThePortAfterAStart::notForwarded())), HowLongItHasFailed::notFailing(), false],
    'failing' => [WhenItSays::unsaid(), TheCheckKept::none(), HowLongItHasFailed::after(2, WhenItSays::unsaid()), false],
]);

it('draws each port a check came to with the numbers its key names and nought for the rest', function (ThePortAfterAStart $port, APortAfterAStartAsShown $drawn): void {
    $read = new HowTheLastStartReads()->this(WhatTheLastStartCameTo::said(WhenItSays::at(Instant::atEpochSeconds(50)), TheCheckKept::this(aCheckWithThePort($port)), TheCheckKept::none(), '', HowLongItHasFailed::notFailing()), Instant::atEpochSeconds(200));

    expect($read->check->port)->toEqual($drawn);
})->with([
    'the same' => [ThePortAfterAStart::same(51413, everyClientTakesIt: true), new APortAfterAStartAsShown('restarts.port.same', 51413, 51413)],
    'moved' => [ThePortAfterAStart::moved(51413, 51999, everyClientTakesIt: true), new APortAfterAStartAsShown('restarts.port.moved', 51413, 51999)],
    'named only after' => [ThePortAfterAStart::cameUpOn(51999, everyClientTakesIt: true), new APortAfterAStartAsShown('restarts.port.now', 0, 51999)],
    'not named' => [ThePortAfterAStart::unsaid(everyClientTakesIt: true), new APortAfterAStartAsShown('restarts.port.unknown', 0, 0)],
    'none forwarded' => [ThePortAfterAStart::notForwarded(), new APortAfterAStartAsShown('', 0, 0)],
]);
