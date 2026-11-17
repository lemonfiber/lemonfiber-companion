<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\Playing;

use function expect;
use function it;

use Modules\Household\Internal\Playing\AChannelOnScreen;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;

use function str_repeat;

/** A channel just tuned, its passes lapsing at 1000. Named for this file. */
function aChannelJustTuned(string $channel = 'c1'): AChannelOnScreen
{
    return AChannelOnScreen::of(
        Stack::of(StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))), StackName::of('The loft'), Address::of('https://192.168.1.42:8443'), Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS))),
        Whose::member('ana'),
        ChannelId::called($channel),
        ALiveStream::opened('s1', 'https://door.home/a.ts?lfpass=p', '', Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)), Instant::atEpochSeconds(1_000)),
    );
}

it('lapses for no reason until a renewal meets one, and forgets the reason once renewed', function (): void {
    $unreached = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $refused = Obstacle::of(KindOfObstacle::NotForThisAccount);
    $renewed = aChannelJustTuned()->unreached($unreached)->renewedUntil(Instant::atEpochSeconds(1_300));

    expect(aChannelJustTuned()->whyItLapses())->toBeNull()
        ->and(aChannelJustTuned()->wasRefused())->toBeFalse()
        ->and(aChannelJustTuned()->unreached($unreached)->whyItLapses())->toBe($unreached)
        ->and(aChannelJustTuned()->unreached($unreached)->wasRefused())->toBeFalse()
        ->and(aChannelJustTuned()->refused($refused)->whyItLapses())->toBe($refused)
        ->and(aChannelJustTuned()->refused($refused)->wasRefused())->toBeTrue()
        ->and(aChannelJustTuned()->refused($refused)->unreached($unreached)->whyItLapses())->toBe($unreached)
        ->and($renewed->whyItLapses())->toBeNull()
        ->and($renewed->stream()->lapses()->epochSeconds())->toBe(1_300)
        ->and(aChannelJustTuned()->refused($refused)->renewedUntil(Instant::atEpochSeconds(1_300))->wasRefused())->toBeFalse();
});

it('keeps how the last channel stopped against that channel alone, and forgets it on the next', function (): void {
    $held = new TheChannelOnScreen();
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    $held->began(aChannelJustTuned('c1'));
    $held->lapsed($why);

    expect($held->now())->toBeNull()
        ->and($held->whyItLapsedOn(ChannelId::called('c1')))->toBe($why)
        ->and($held->whyItLapsedOn(ChannelId::called('c2')))->toBeNull()
        ->and($held->howItStoppedOn(ChannelId::called('c1')))->toBe(PlaybackIs::Closed);

    $held->began(aChannelJustTuned('c2'));
    $held->stoppedOnThePlayer(PlaybackIs::StoppedByThePin);

    expect($held->whyItLapsedOn(ChannelId::called('c1')))->toBeNull()
        ->and($held->whyItLapsedOn(ChannelId::called('c2')))->toBeNull()
        ->and($held->howItStoppedOn(ChannelId::called('c2')))->toBe(PlaybackIs::StoppedByThePin)
        ->and($held->howItStoppedOn(ChannelId::called('c1')))->toBe(PlaybackIs::Closed);
});

it('keeps nothing where a channel stops with none on the player, and lets one go when it is over', function (): void {
    $held = new TheChannelOnScreen();
    $held->lapsed(Obstacle::of(KindOfObstacle::StackDidNotAnswer));

    expect($held->whyItLapsedOn(ChannelId::called('c1')))->toBeNull();

    $held->began(aChannelJustTuned());
    $held->kept(aChannelJustTuned()->renewedUntil(Instant::atEpochSeconds(2_000)));

    expect($held->now()?->stream()->lapses()->epochSeconds())->toBe(2_000);

    $held->over();

    expect($held->now())->toBeNull()
        ->and($held->howItStoppedOn(ChannelId::called('c1')))->toBe(PlaybackIs::Closed);
});
