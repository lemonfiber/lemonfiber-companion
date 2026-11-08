<?php

declare(strict_types=1);
use Modules\Kernel\Api\AnAlert;
use Modules\Kernel\Api\AnAlertsName;
use Modules\Kernel\Api\AnAlertsSort;
use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\TheAlerts;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhichWayItWent;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\PinnedClients;
use Psr\Log\NullLogger;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\NotesKeptInMemory;
use Tests\Support\Fakes\WhereHearingLeftOffInMemory;
use Tests\Support\TheStreamListenedTo;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;
use Tests\Support\WhereItResumes;

// The alerts the Hearing contract carries, run against the adapter and against the fake.
//
// Every screen and every notification that follows an alert is handed what
// these answer, so a fake that answered in a way the adapter never does would
// let them pass against a stack that cannot exist.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * One `alert` envelope as the core writes it, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function anAlertEnvelope(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'alert',
        'data' => [
            'check' => 'vpn.tunnel',
            'kind' => 'vpn.down',
            'moment' => 'onset',
            'severity' => 'critical',
            'notification' => 'problem',
            'summary' => 'The tunnel dropped',
            'meaning' => 'Downloads have stopped rather than leak',
            'remedies' => ['Restart the gateway'],
            'affected' => ['vpn.tunnel'],
            'id' => 'vpn.tunnel#2',
            ...$changed,
        ],
    ];
}

/**
 * One `alert` event as the core frames it, changed where a case says.
 *
 * @param array<mixed> $changed
 */
function anAlertEvent(array $changed = []): string
{
    return TheStreamListenedTo::anEvent('alert', json_encode(anAlertEnvelope($changed), JSON_THROW_ON_ERROR));
}

/** Which arm of what was heard a wake took, as a word. */
function theArmAWakeTook(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(): TheWordCarriedOut => new TheWordCarriedOut('said'),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('hears every alert a wake carried, in order, beside whatever else it said', function (): void {
    $raised = [
        AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, AnAlertsName::of('vpn.tunnel#2'), AnAlertsSort::of(KindOfNotification::Problem, Severity::Critical)),
        AnAlert::unnamed(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Resolved, AnAlertsSort::of(KindOfNotification::Problem, Severity::Critical)),
    ];

    foreach (TheStreamListenedTo::everyWay(
        [MockResponse::make(sprintf('%s%s', anAlertEvent(), anAlertEvent(['moment' => 'resolved', 'id' => null])))],
        AStackThatSpeaksUp::thenEnding(...[WhatWasHeard::aSignOfLife()->alerting(TheAlerts::of(...$raised))]),
    ) as $which => $make) {
        expect(TheStreamListenedTo::theAlertsAWakeHeard($make()))->toEqual($raised, $which);
    }
});

it('skips an alert it cannot read, notes why without what the stack sent, and still hears the rest and the stream', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(sprintf('%s%s', anAlertEvent($changed), anAlertEvent()))]);
    $notes = new NotesKeptInMemory();

    $heard = new Listeners(new PinnedClients(), $notes, WhereHearingLeftOffInMemory::nowhere())->howItIs(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession());

    expect(theArmAWakeTook($heard))->toBe('alive')
        ->and(iterator_to_array($heard->theAlerts(), preserve_keys: false))
        ->toEqual([AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, AnAlertsName::of('vpn.tunnel#2'), AnAlertsSort::of(KindOfNotification::Problem, Severity::Critical))])
        ->and($notes->noted())->toHaveCount(1)
        ->and($notes->noted()[0])->toStartWith('alert: skipped one this app cannot read')
        ->and($notes->noted()[0])->not->toContain('Click here')
        ->and($notes->noted()[0])->not->toContain('sideways');
})->with([
    'no kind' => [['kind' => ' ']],
    'a kind that is a sentence' => [['kind' => 'Click here to restore your files']],
    'a kind in capitals' => [['kind' => 'VPN.Down']],
    'no way it went' => [['moment' => null]],
    'a way this app does not read' => [['moment' => 'sideways']],
    'a name that is not words' => [['id' => 7]],
    'no payload' => [['kind' => null, 'moment' => null]],
    'a kind of notification this app does not read' => [['notification' => 'sideways']],
    'no severity' => [['severity' => null]],
    'a severity this app does not read' => [['severity' => 'sideways']],
]);

it('hears the kind of notification and the severity each alert carried, and a problem where it carried no kind', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(sprintf('%s%s', anAlertEvent(['notification' => 'backup', 'severity' => 'advisory']), anAlertEvent(['notification' => null, 'severity' => 'error'])))]);

    $heard = new Listeners(new PinnedClients(), new NotesKeptInMemory(), WhereHearingLeftOffInMemory::nowhere())->howItIs(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession());

    expect(array_map(static fn(AnAlert $alert): AnAlertsSort => $alert->sort(), iterator_to_array($heard->theAlerts(), preserve_keys: false)))
        ->toEqual([AnAlertsSort::of(KindOfNotification::Backup, Severity::Advisory), AnAlertsSort::of(KindOfNotification::Problem, Severity::Error)]);
});

it('stands in for a stack with an alert the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AlertEnvelope', anAlertEnvelope()))->toBe([]);
});

it('opens after where an earlier launch left off, and hears the alerts the stack hands back', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(anAlertEvent())]);
    $leftOff = WhereHearingLeftOffInMemory::nowhere()->heardBefore(TheStreamListenedTo::aStack()->id(), AnEventHeard::named('run-0'));

    $alerts = TheStreamListenedTo::theAlertsAWakeHeard(new Listeners(new PinnedClients(), new NullLogger(), $leftOff));

    expect(WhereEachOpenResumed::in($stack))->toBe(['run-0'])
        ->and($alerts)->toEqual([AnAlert::raised(WhatTheCoreDecided::toSay('vpn.down'), WhichWayItWent::Onset, AnAlertsName::of('vpn.tunnel#2'), AnAlertsSort::of(KindOfNotification::Problem, Severity::Critical))])
        ->and(WhereItResumes::said($leftOff->leftOffAt(TheStreamListenedTo::aStack()->id())))->toBe('after:run-1');
});

it('notes nothing for an event whose identifier is empty', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(str_replace('id: run-1', 'id: ', anAlertEvent()))]);
    $leftOff = WhereHearingLeftOffInMemory::nowhere();

    $alerts = TheStreamListenedTo::theAlertsAWakeHeard(new Listeners(new PinnedClients(), new NullLogger(), $leftOff));

    expect($alerts)->toHaveCount(1)
        ->and($leftOff->timesNoted())->toBe(0);
});
