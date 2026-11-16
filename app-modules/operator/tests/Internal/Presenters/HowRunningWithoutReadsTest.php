<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\APartTheWordsLeaveOut;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePartsTheWordsLeaveOut;
use Modules\Kernel\Api\WhoseStackItIs;
use Modules\Operator\Internal\Presenters\HowRunningWithoutReads;
use Modules\Operator\Internal\ViewModels\APartLeftOutAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\RunningWithoutAsShown;

it('draws nothing of running without lemonfiber where the session ended or the stack could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(new HowRunningWithoutReads()->signedOut())->toEqual(new RunningWithoutAsShown(HowTheReadingWent::theSessionEnded(), '', '', '', '', '', []))
        ->and(new HowRunningWithoutReads()->met($why))->toEqual(new RunningWithoutAsShown(HowTheReadingWent::somethingStopped($why), '', '', '', '', '', []));
});

it('draws every part of how the operator\'s own stack runs without lemonfiber, and no directory for the bundled one', function (): void {
    $words = TheCommandLine::of('docker', 'compose', 'up', '-d');
    $own = HowItRunsWithoutLemonfiber::said(WhoseStackItIs::theOperators('/home/anna/stack'), '/home/anna/stack', '/home/anna/stack/.env', $words, 'lemonfiber --yes', ThePartsTheWordsLeaveOut::these(APartTheWordsLeaveOut::because('gluetun', 'It needs a key first')));
    $bundled = HowItRunsWithoutLemonfiber::said(WhoseStackItIs::bundled(), '/srv/lemonfiber', '/srv/lemonfiber/.env', $words, 'lemonfiber --yes', ThePartsTheWordsLeaveOut::these());

    expect(new HowRunningWithoutReads()->this($own))->toEqual(new RunningWithoutAsShown(HowTheReadingWent::itCameBack(), '/home/anna/stack', '/home/anna/stack', '/home/anna/stack/.env', 'docker compose up -d', 'lemonfiber --yes', [new APartLeftOutAsShown('gluetun', 'It needs a key first')]))
        ->and(new HowRunningWithoutReads()->this($bundled)->ownDirectory)->toBe('');
});
