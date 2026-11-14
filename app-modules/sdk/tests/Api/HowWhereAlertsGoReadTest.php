<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_merge;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ADelivery;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\ARetry;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Sdk\Api\HowWhereAlertsGoRead;
use Modules\Sdk\Api\WhereAlertsGoIsUnreadable;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * @param  array<mixed>    $data
 * @return Envelope<mixed>
 */
function notifySaying(array $data): Envelope
{
    return new Envelope(1, 'notify', $data);
}

/**
 * @param  array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function aBackEndRow(array $overrides = []): array
{
    return array_merge([
        'id' => 'ntfy-home',
        'kind' => 'ntfy',
        'where' => 'ntfy.home.example/alerts',
        'chosen_by' => 'operator',
        'proof' => 'unproven',
        'failures' => [],
    ], $overrides);
}

/**
 * @param  list<mixed>          $backEnds
 * @param  list<mixed>          $unread
 * @return array<string, mixed>
 */
function aNotifyReport(array $backEnds, array $unread = [], string $state = 'ready'): array
{
    return ['state' => $state, 'rehearsed' => false, 'backends' => $backEnds, 'unread' => $unread];
}

function theOneBackEndIn(WhereAlertsGo $read): AnAlertBackEnd
{
    foreach ($read->backEnds as $backEnd) {
        return $backEnd;
    }

    throw new WhereAlertsGoIsUnreadable('no back-end was read');
}

function howTheDeliveryEnded(ADelivery $delivery): string
{
    return $delivery->ended->either(
        confirmed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('confirmed'),
        sent: static fn(): TheWordCarriedOut => new TheWordCarriedOut('sent'),
        failed: static fn(string $reason): TheWordCarriedOut => new TheWordCarriedOut(sprintf('failed: %s', $reason)),
    )->said;
}

function whatIsReadOfTheBackEnd(AnAlertBackEnd $backEnd): string
{
    $said = [
        $backEnd->id()->named(),
        $backEnd->kind()->value,
        $backEnd->where(),
        $backEnd->chosenBy()->either(
            operator: static fn(): TheWordCarriedOut => new TheWordCarriedOut('operator'),
            member: static fn(string $name): TheWordCarriedOut => new TheWordCarriedOut(sprintf('member %s', $name)),
        )->said,
        $backEnd->hosted()->either(
            notHosted: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not hosted'),
            hosted: static fn(string $host, array $leaves): TheWordCarriedOut => new TheWordCarriedOut(sprintf('hosted at %s leaving %s', $host, implode(' + ', $leaves))),
        )->said,
        $backEnd->proof()->either(
            unproven: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unproven'),
            proven: static fn(Instant $at): TheWordCarriedOut => new TheWordCarriedOut(sprintf('proven %d', $at->epochSeconds())),
            held: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('held: %s', $why)),
        )->said,
        $backEnd->deliveries()->last(
            delivered: static fn(ADelivery $last): TheWordCarriedOut => new TheWordCarriedOut(sprintf('last %d %s %s', $last->at->epochSeconds(), $last->kind->value, howTheDeliveryEnded($last))),
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no delivery'),
        )->said,
        sprintf('%d failed', $backEnd->deliveries()->failures()->count()),
        $backEnd->deliveries()->retry(
            waits: static fn(ARetry $retry): TheWordCarriedOut => new TheWordCarriedOut(sprintf('attempt %d of %d at %d', $retry->attempt, $retry->of, $retry->next->epochSeconds())),
            none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no retry'),
        )->said,
    ];

    return implode(' | ', $said);
}

it('reads a back-end a member chose, hosted, failing and waiting to be tried again', function (): void {
    $read = HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow([
        'kind' => 'gotify',
        'chosen_by' => ['member' => 'Anna'],
        'hosted' => ['host' => 'push.example.com', 'leaves' => ['the alert\'s title', 'this machine\'s address']],
        'proof' => ['proven' => 1_790_000_000],
        'last' => ['at' => 1_790_000_600, 'kind' => 'problem', 'outcome' => ['failed' => '502 Bad Gateway']],
        'failures' => [
            ['at' => 1_790_000_600, 'kind' => 'problem', 'outcome' => ['failed' => '502 Bad Gateway']],
            ['at' => 1_790_000_300, 'kind' => 'backup', 'outcome' => ['failed' => 'timed out']],
        ],
        'retry' => ['attempt' => 3, 'of' => 5, 'next' => 1_790_000_900],
    ])], state: 'degraded')));

    expect($read->stands)->toBe(HowDeliveryStands::Degraded)
        ->and(whatIsReadOfTheBackEnd(theOneBackEndIn($read)))->toBe(
            'ntfy-home | gotify | ntfy.home.example/alerts | member Anna | hosted at push.example.com leaving the alert\'s title + this machine\'s address'
            . ' | proven 1790000000 | last 1790000600 problem failed: 502 Bad Gateway | 2 failed | attempt 3 of 5 at 1790000900',
        )
        ->and($read->isNotSetUp())->toBeFalse();
});

it('reads a back-end the operator chose that has never delivered', function (): void {
    expect(whatIsReadOfTheBackEnd(theOneBackEndIn(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow()]))))))
        ->toBe('ntfy-home | ntfy | ntfy.home.example/alerts | operator | not hosted | unproven | no delivery | 0 failed | no retry');
});

it('reads a held back-end, and absent arms written as null as absent', function (): void {
    expect(whatIsReadOfTheBackEnd(theOneBackEndIn(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow([
        'proof' => ['held' => 'the topic was refused'],
        'hosted' => null,
        'last' => null,
        'retry' => null,
    ])]))))))->toBe('ntfy-home | ntfy | ntfy.home.example/alerts | operator | not hosted | held: the topic was refused | no delivery | 0 failed | no retry');
});

it('tells a confirmed delivery from one only sent', function (string $outcome, string $said): void {
    $backEnd = theOneBackEndIn(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow([
        'proof' => ['proven' => 1_790_000_000],
        'last' => ['at' => 1_790_000_000, 'kind' => 'update', 'outcome' => $outcome],
    ])]))));

    expect($backEnd->deliveries()->last(
        delivered: static fn(ADelivery $last): TheWordCarriedOut => new TheWordCarriedOut(howTheDeliveryEnded($last)),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said)->toBe($said);
})->with([
    'confirmed' => ['confirmed', 'confirmed'],
    'sent' => ['sent', 'sent'],
]);

it('reads every kind of back-end the contract names', function (KindOfAlertBackEnd $kind): void {
    expect(theOneBackEndIn(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow(['kind' => $kind->value])]))))->kind())->toBe($kind);
})->with(KindOfAlertBackEnd::cases());

it('reads every kind of notification a delivery can carry', function (KindOfNotification $kind): void {
    $backEnd = theOneBackEndIn(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([aBackEndRow([
        'last' => ['at' => 1, 'kind' => $kind->value, 'outcome' => 'sent'],
    ])]))));

    expect($backEnd->deliveries()->last(
        delivered: static fn(ADelivery $last): TheWordCarriedOut => new TheWordCarriedOut($last->kind->value),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said)->toBe($kind->value);
})->with(KindOfNotification::cases());

it('reads the back-ends whose records could not be read, apart from the readable ones', function (): void {
    $read = HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([], [['backend' => 'gotify-old', 'because' => 'its record is corrupt']], state: 'unconfigured')));
    $unread = [];

    foreach ($read->unread as $one) {
        $unread[] = sprintf('%s: %s', $one->id->named(), $one->because);
    }

    expect($unread)->toBe(['gotify-old: its record is corrupt'])
        ->and($read->backEnds->isEmpty())->toBeTrue()
        ->and($read->isNotSetUp())->toBeFalse();
});

it('reads a machine with nothing set up as not set up', function (): void {
    expect(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([], state: 'unconfigured')))->isNotSetUp())->toBeTrue()
        ->and(HowWhereAlertsGoRead::in(notifySaying(aNotifyReport([], state: 'unreachable')))->isNotSetUp())->toBeFalse();
});

it('refuses an answer short of what the contract carries, naming where', function (mixed $data, string $where): void {
    expect(static fn(): WhereAlertsGo => HowWhereAlertsGoRead::in(new Envelope(1, 'notify', $data)))
        ->toThrow(WhereAlertsGoIsUnreadable::class, sprintf('`%s`', $where));
})->with([
    'no payload' => ['not a payload', 'data'],
    'no state' => [['backends' => [], 'unread' => []], 'state'],
    'a state nobody reads' => [aNotifyReport([], state: 'sleeping'), 'state'],
    'no back-ends' => [['state' => 'ready', 'unread' => []], 'backends'],
    'no unread list' => [['state' => 'ready', 'backends' => []], 'unread'],
    'a back-end that is not a record' => [aNotifyReport(['ntfy']), 'backends.0'],
    'a kind nobody reads' => [aNotifyReport([aBackEndRow(['kind' => 'pigeon'])]), 'backends.0.kind'],
    'no id' => [aNotifyReport([aBackEndRow(['id' => 7])]), 'backends.0.id'],
    'no where' => [aNotifyReport([aBackEndRow(['where' => null])]), 'backends.0.where'],
    'a chooser that is neither' => [aNotifyReport([aBackEndRow(['chosen_by' => 'somebody'])]), 'backends.0.chosen_by'],
    'a member with no name' => [aNotifyReport([aBackEndRow(['chosen_by' => ['member' => 3]])]), 'backends.0.chosen_by.member'],
    'a proof that is neither' => [aNotifyReport([aBackEndRow(['proof' => 'maybe'])]), 'backends.0.proof'],
    'a proof proven at no time' => [aNotifyReport([aBackEndRow(['proof' => ['proven' => 'today']])]), 'backends.0.proof.held'],
    'a host with nothing it sends' => [aNotifyReport([aBackEndRow(['hosted' => ['host' => 'push.example.com']])]), 'backends.0.hosted.leaves'],
    'a host sending something unwritten' => [aNotifyReport([aBackEndRow(['hosted' => ['host' => 'push.example.com', 'leaves' => [4]]])]), 'backends.0.hosted.leaves.0'],
    'a hosted back-end with no host' => [aNotifyReport([aBackEndRow(['hosted' => ['leaves' => []]])]), 'backends.0.hosted.host'],
    'no failures' => [aNotifyReport([array_merge(aBackEndRow(), ['failures' => 'none'])]), 'backends.0.failures'],
    'a delivery at no time' => [aNotifyReport([aBackEndRow(['last' => ['kind' => 'problem', 'outcome' => 'sent']])]), 'backends.0.last.at'],
    'a delivery of a kind nobody reads' => [aNotifyReport([aBackEndRow(['last' => ['at' => 1, 'kind' => 'gossip', 'outcome' => 'sent']])]), 'backends.0.last.kind'],
    'an outcome that is neither' => [aNotifyReport([aBackEndRow(['failures' => [['at' => 1, 'kind' => 'problem', 'outcome' => 'lost']]])]), 'backends.0.failures.0.outcome'],
    'a failure with no reason' => [aNotifyReport([aBackEndRow(['failures' => [['at' => 1, 'kind' => 'problem', 'outcome' => ['failed' => 5]]]])]), 'backends.0.failures.0.outcome.failed'],
    'a retry that is not a record' => [aNotifyReport([aBackEndRow(['retry' => 'soon'])]), 'backends.0.retry'],
    'a retry with no attempt' => [aNotifyReport([aBackEndRow(['retry' => ['of' => 5, 'next' => 1]])]), 'backends.0.retry.attempt'],
    'a retry of no number' => [aNotifyReport([aBackEndRow(['retry' => ['attempt' => 1, 'next' => 1]])]), 'backends.0.retry.of'],
    'a retry at no time' => [aNotifyReport([aBackEndRow(['retry' => ['attempt' => 1, 'of' => 5]])]), 'backends.0.retry.next'],
    'an unread record that is not a record' => [aNotifyReport([], ['gotify-old']), 'unread.0'],
    'an unread record naming no back-end' => [aNotifyReport([], [['because' => 'corrupt']]), 'unread.0.backend'],
    'an unread record with no reason' => [aNotifyReport([], [['backend' => 'gotify-old']]), 'unread.0.because'],
]);

it('builds its payloads in the shape the contract accepts', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('NotifyEnvelope', [
        'api_version' => 1,
        'kind' => 'notify',
        'data' => aNotifyReport([aBackEndRow([
            'chosen_by' => ['member' => 'Anna'],
            'hosted' => ['host' => 'push.example.com', 'leaves' => ['the alert\'s title']],
            'proof' => ['proven' => 1_790_000_000],
            'last' => ['at' => 1_790_000_600, 'kind' => 'problem', 'outcome' => ['failed' => '502 Bad Gateway']],
            'failures' => [['at' => 1_790_000_600, 'kind' => 'problem', 'outcome' => ['failed' => '502 Bad Gateway']]],
            'retry' => ['attempt' => 3, 'of' => 5, 'next' => 1_790_000_900],
        ])], [['backend' => 'gotify-old', 'because' => 'its record is corrupt']], state: 'degraded'),
    ]))->toBe([]);
});
