<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADelivery;
use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AlertBackEnds;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\AnUnreadBackEnd;
use Modules\Kernel\Api\ARetry;
use Modules\Kernel\Api\FailedDeliveries;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowADeliveryEnded;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\HowFarItIsProven;
use Modules\Kernel\Api\HowItsDeliveriesWent;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\UnreadBackEnds;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Kernel\Api\WhereItIsHosted;
use Modules\Kernel\Api\WhoChoseIt;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\OneWayAlertsGo;
use Modules\Operator\Internal\Screens\WhereAlertsGoHere;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Internal\TheMenu;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysWhereAlertsGo;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

const WHEN_ALERTS_ARE_READ = 1_790_000_000;

function theStackWhoseBackEndsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('w', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

function aFailedDelivery(int $secondsAgo, string $reason): ADelivery
{
    return new ADelivery(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ - $secondsAgo), KindOfNotification::Problem, HowADeliveryEnded::failed($reason));
}

function aBackEndFailing(): AnAlertBackEnd
{
    return AnAlertBackEnd::configured(
        AlertBackEndId::called('gotify/cloud'),
        KindOfAlertBackEnd::Gotify,
        'push.example.com/application',
        WhoChoseIt::aMember('Anna'),
        WhereItIsHosted::at('push.example.com', 'The alert\'s title and text'),
        HowFarItIsProven::provenAt(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ - 7_200)),
        HowItsDeliveriesWent::lastly(aFailedDelivery(120, '502 Bad Gateway'))
            ->failedSince(FailedDeliveries::these(aFailedDelivery(120, '502 Bad Gateway'), aFailedDelivery(600, 'timed out')))
            ->retrying(new ARetry(3, 5, Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ + 300))),
    );
}

function aBackEndThat(string $id, HowFarItIsProven $proof, HowItsDeliveriesWent $deliveries): AnAlertBackEnd
{
    return AnAlertBackEnd::configured(AlertBackEndId::called($id), KindOfAlertBackEnd::Ntfy, sprintf('ntfy.home.example/%s', $id), WhoChoseIt::theOperator(), WhereItIsHosted::notHosted(), $proof, $deliveries);
}

function whereAlertsGo(AnAlertBackEnd ...$backEnds): WhereAlertsGo
{
    return WhereAlertsGo::read(HowDeliveryStands::Degraded, AlertBackEnds::these(...$backEnds), UnreadBackEnds::these());
}

function aKeychainHolding(Stack $stack, bool $signedIn): AKeychainInMemory
{
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    return $keychain;
}

function whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo $alerts, bool $signedIn = true): WhereAlertsGoHere
{
    $stack = theStackWhoseBackEndsAreRead();
    $screen = new WhereAlertsGoHere($alerts, aKeychainHolding($stack, $signedIn), FrozenClock::at(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ)), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

function oneBackEndDrawn(AStackThatSaysWhereAlertsGo $alerts, string $about, bool $signedIn = true): OneWayAlertsGo
{
    $stack = theStackWhoseBackEndsAreRead();
    $screen = new OneWayAlertsGo($alerts, aKeychainHolding($stack, $signedIn), FrozenClock::at(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ)), AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $about]);

    return $screen;
}

it('draws how delivery stands, and each back-end with what it is, where, who chose it, its proof and its newest delivery', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndFailing()))))->said();

    expect($drawn)->toContain(__('alerts.state.degraded'))
        ->and($drawn)->toContain(__('alerts.kind.gotify'))
        ->and($drawn)->toContain('push.example.com/application')
        ->and($drawn)->toContain(__('alerts.chosen.member', ['member' => 'Anna']))
        ->and($drawn)->toContain(__('alerts.proof.proven', ['ago' => trans_choice('health.ago.hours', 2)]))
        ->and($drawn)->toContain(__('alerts.ended.failed', ['ago' => trans_choice('health.ago.minutes', 2), 'reason' => '502 Bad Gateway']))
        ->and($drawn)->toContain(trans_choice('alerts.failed_since', 2))
        ->and($drawn)->toContain(__('alerts.retry', ['in' => trans_choice('health.in.minutes', 5), 'attempt' => 3, 'of' => 5]))
        ->and($drawn)->toContain(__('alerts.hosted_at', ['host' => 'push.example.com']))
        ->and($drawn)->toContain('The alert\'s title and text')
        ->and($drawn)->toContain(__('alerts.changed_at_the_machine'));
});

it('tells a back-end never delivered, one confirmed and one only sent apart', function (): void {
    $proven = HowFarItIsProven::provenAt(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ - 60));
    $drawn = WhatTheDeviceWouldDraw::by(whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(
        aBackEndThat('never', HowFarItIsProven::unproven(), HowItsDeliveriesWent::nothingYet()),
        aBackEndThat('confirmed', $proven, HowItsDeliveriesWent::lastly(new ADelivery(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ - 60), KindOfNotification::Backup, HowADeliveryEnded::confirmed()))),
        aBackEndThat('sent', $proven, HowItsDeliveriesWent::lastly(new ADelivery(Instant::atEpochSeconds(WHEN_ALERTS_ARE_READ - 60), KindOfNotification::Backup, HowADeliveryEnded::sent()))),
    ))))->said();

    expect($drawn)->toContain(__('alerts.proof.unproven'))
        ->and($drawn)->toContain(__('alerts.ended.none'))
        ->and($drawn)->toContain(__('alerts.chosen.you'))
        ->and($drawn)->toContain(__('alerts.ended.confirmed', ['ago' => trans_choice('health.ago.minutes', 1)]))
        ->and($drawn)->toContain(__('alerts.ended.sent', ['ago' => trans_choice('health.ago.minutes', 1)]))
        ->and($drawn)->not->toContain(trans_choice('alerts.failed_since', 1));
});

it('draws a held back-end with the core\'s reason', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(
        aBackEndThat('held', HowFarItIsProven::held('the topic was refused'), HowItsDeliveriesWent::nothingYet()),
    ))))->said();

    expect($drawn)->toContain(__('alerts.proof.held', ['why' => 'the topic was refused']));
});

it('lists the back-ends the core could not read apart, and never as none', function (): void {
    $screen = whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(
        HowDeliveryStands::Unconfigured,
        AlertBackEnds::these(),
        UnreadBackEnds::these(AnUnreadBackEnd::because(AlertBackEndId::called('gotify-old'), 'its record is corrupt')),
    )));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->unread)->toBe(['gotify-old' => 'its record is corrupt'])
        ->and($drawn)->toContain(__('alerts.unread'))
        ->and($drawn)->toContain('gotify-old')
        ->and($drawn)->toContain('its record is corrupt')
        ->and($drawn)->toContain(__('alerts.none_readable'))
        ->and($drawn)->not->toContain(__('alerts.set_up_at_the_machine'));
});

it('draws a machine with nothing set up as the one not set up frame', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(whereAlertsGoDrawn(AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(
        HowDeliveryStands::Unconfigured,
        AlertBackEnds::these(),
        UnreadBackEnds::these(),
    ))))->said();

    expect($drawn)->toContain(__('alerts.state.unconfigured'))
        ->and($drawn)->toContain(__('alerts.set_up_at_the_machine'))
        ->and($drawn)->not->toContain(__('alerts.none_readable'));
});

it('draws a refused read as what stood in the way, never as no back-ends', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $screen = whereAlertsGoDrawn($alerts);
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->stateSaid)->toBe('')
        ->and($screen->answer()->backEnds)->toBe([])
        ->and($screen->answer()->unread)->toBe([])
        ->and($screen->answer()->notSetUp)->toBeFalse()
        ->and($drawn)->not->toContain(__('alerts.state.unconfigured'))
        ->and($drawn)->not->toContain(__('alerts.none_readable'))
        ->and($alerts->askings)->toBe(1);
});

it('asks nothing where this phone holds no session for the machine', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndFailing()));
    $screen = whereAlertsGoDrawn($alerts, signedIn: false);

    expect($screen->answer()->went->isSignedIn)->toBeFalse()
        ->and($screen->answer()->backEnds)->toBe([])
        ->and($alerts->askings)->toBe(0);
});

it('asks once however often the frame reads what came back', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndFailing()));
    $screen = whereAlertsGoDrawn($alerts);

    $screen->answer();
    $screen->answer();

    expect($alerts->askings)->toBe(1);
});

it('opens each back-end\'s own page, which lists every failure since the last confirmed delivery with its reason', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndFailing(), aBackEndThat('other', HowFarItIsProven::unproven(), HowItsDeliveriesWent::nothingYet())));
    $list = whereAlertsGoDrawn($alerts);
    $page = oneBackEndDrawn($alerts, 'gotify/cloud');
    $drawn = WhatTheDeviceWouldDraw::by($page)->said();

    expect($list->answer()->backEnds[0]->goes)->toBe(AStacksScreen::AlertBackEnd->forTheStacks(theStackWhoseBackEndsAreRead()->id(), AlertBackEndId::called('gotify/cloud')))
        ->and($page->answer()->backEnds)->toHaveCount(1)
        ->and($drawn)->toContain(__('alerts.failures'))
        ->and($drawn)->toContain(__('alerts.ended.failed', ['ago' => trans_choice('health.ago.minutes', 10), 'reason' => 'timed out']))
        ->and($drawn)->toContain(__('notifications.kind.problem'))
        ->and($drawn)->not->toContain('ntfy.home.example/other')
        ->and($drawn)->not->toContain(__('alerts.open', ['where' => 'push.example.com/application']));
});

it('says a back-end with no failures has none, and one no longer listed is no longer listed', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndThat('quiet', HowFarItIsProven::unproven(), HowItsDeliveriesWent::nothingYet())));

    expect(WhatTheDeviceWouldDraw::by(oneBackEndDrawn($alerts, 'quiet'))->said())->toContain(__('alerts.no_failures'))
        ->and(WhatTheDeviceWouldDraw::by(oneBackEndDrawn($alerts, 'gone'))->said())->toContain(__('alerts.no_longer_listed'));
});

it('draws a refused read on a back-end\'s own page as what stood in the way', function (): void {
    $page = oneBackEndDrawn(AStackThatSaysWhereAlertsGo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), 'quiet');

    expect($page->answer()->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($page)->said())->not->toContain(__('alerts.no_longer_listed'));
});

it('asks nothing on a back-end\'s own page where this phone holds no session', function (): void {
    $alerts = AStackThatSaysWhereAlertsGo::saying(whereAlertsGo(aBackEndFailing()));

    expect(oneBackEndDrawn($alerts, 'gotify/cloud', signedIn: false)->answer()->went->isSignedIn)->toBeFalse()
        ->and($alerts->askings)->toBe(0);
});

it('is in the stack\'s menu beside its alerts', function (): void {
    expect(TheMenu::WhereAlertsGo->screen())->toBe(AStacksScreen::Alerts)
        ->and(TheMenu::WhereAlertsGo->group())->toBe(TheMenu::Alerts->group());
});
