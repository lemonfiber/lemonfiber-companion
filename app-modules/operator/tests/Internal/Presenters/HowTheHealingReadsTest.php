<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadInTheQueue;
use Modules\Kernel\Api\AWedgedItem;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheQueueHeals;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\WhetherHealingActs;
use Modules\Kernel\Api\WhyItIsWedged;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheHealingReads;
use Modules\Operator\Internal\ViewModels\AQueuedDownloadAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheHealingAsShown;

it('draws nothing of the queue where the session ended or the stack could not be asked', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    expect(new HowTheHealingReads()->signedOut())->toEqual(new TheHealingAsShown(HowTheReadingWent::theSessionEnded(), '', 0, '', 0, AgoAsShown::saysNothing(), incomplete: false, rehearsed: false, mismatch: '', items: [], unreachable: [], runs: []))
        ->and(new HowTheHealingReads()->met($why))->toEqual(new TheHealingAsShown(HowTheReadingWent::somethingStopped($why), '', 0, '', 0, AgoAsShown::saysNothing(), incomplete: false, rehearsed: false, mismatch: '', items: [], unreachable: [], runs: []));
});

it('names a download by its title with its release beside it, or by its release alone', function (): void {
    $queue = HowTheQueueHeals::switched(WhetherHealingActs::On, 3, HowLong::ofSeconds(60), WhenItSays::unsaid(), '')->finding(
        AWedgedItem::flagged(ADownloadInTheQueue::heldBy(ServiceId::called('sonarr'), 'Show.S01E01', 'Show'), WhyItIsWedged::Slow, 'Too slow', 1),
        AWedgedItem::flagged(ADownloadInTheQueue::heldBy(ServiceId::called('qbittorrent'), 'Unknown.Release', ''), WhyItIsWedged::Orphaned, 'In no queue', 1),
    );
    $shown = new HowTheHealingReads()->this($queue, Instant::atEpochSeconds(1_790_000_000));

    expect($shown->items[0]->download)->toEqual(new AQueuedDownloadAsShown('Show', 'Show.S01E01', 'sonarr'))
        ->and($shown->items[1]->download)->toEqual(new AQueuedDownloadAsShown('Unknown.Release', '', 'qbittorrent'));
});
