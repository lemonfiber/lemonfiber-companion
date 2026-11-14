<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AlertBackEndId;
use Modules\Kernel\Api\AlertBackEnds;
use Modules\Kernel\Api\AnAlertBackEnd;
use Modules\Kernel\Api\AnUnreadBackEnd;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowDeliveryStands;
use Modules\Kernel\Api\HowFarItIsProven;
use Modules\Kernel\Api\HowItsDeliveriesWent;
use Modules\Kernel\Api\KindOfAlertBackEnd;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KnowingWhereAlertsGo;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\UnreadBackEnds;
use Modules\Kernel\Api\WhereAlertsGo;
use Modules\Kernel\Api\WhereAlertsGoCameBack;
use Modules\Kernel\Api\WhereItIsHosted;
use Modules\Kernel\Api\WhoChoseIt;
use Modules\Sdk\Api\AlertDeliveries;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhereAlertsGo;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackWithAlerts(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the core answers where alerts go with, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatTheCoreSaidOfAlerts(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'notify', 'data' => [...[
        'state' => 'ready',
        'rehearsed' => false,
        'backends' => [['id' => 'ntfy-home', 'kind' => 'ntfy', 'where' => 'ntfy.home.example/alerts', 'chosen_by' => 'operator', 'proof' => 'unproven', 'failures' => []]],
        'unread' => [['backend' => 'gotify-old', 'because' => 'its record is corrupt']],
    ], ...$changed]];
}

function anAlertReaderAnswered(MockResponse $answered): KnowingWhereAlertsGo
{
    MockClient::destroyGlobal();
    MockClient::global([$answered]);

    return new AlertDeliveries(new PinnedClients());
}

function whereTheyGo(WhereAlertsGoCameBack $came): string
{
    return $came->either(
        read: static function (WhereAlertsGo $read): TheWordCarriedOut {
            $said = [$read->stands->value];

            foreach ($read->backEnds as $backEnd) {
                $said[] = sprintf('%s:%s:%s', $backEnd->id()->named(), $backEnd->kind()->value, $backEnd->where());
            }

            foreach ($read->unread as $unread) {
                $said[] = sprintf('unread %s:%s', $unread->id->named(), $unread->because);
            }

            return new TheWordCarriedOut(implode('|', $said));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('reads where alerts go in the core\'s order, the unread apart, either way', function (): void {
    $either = [
        'the fake' => AStackThatSaysWhereAlertsGo::saying(WhereAlertsGo::read(
            HowDeliveryStands::Ready,
            AlertBackEnds::these(AnAlertBackEnd::configured(
                AlertBackEndId::called('ntfy-home'),
                KindOfAlertBackEnd::Ntfy,
                'ntfy.home.example/alerts',
                WhoChoseIt::theOperator(),
                WhereItIsHosted::notHosted(),
                HowFarItIsProven::unproven(),
                HowItsDeliveriesWent::nothingYet(),
            )),
            UnreadBackEnds::these(AnUnreadBackEnd::because(AlertBackEndId::called('gotify-old'), 'its record is corrupt')),
        )),
        'the adapter' => anAlertReaderAnswered(MockResponse::make((string) json_encode(whatTheCoreSaidOfAlerts()))),
    ];

    foreach ($either as $which => $knowing) {
        expect(whereTheyGo($knowing->whereAlertsGoOn(aStackWithAlerts(), Session::of('a-session-not-a-secret'))))
            ->toBe('ready|ntfy-home:ntfy:ntfy.home.example/alerts|unread gotify-old:its record is corrupt', $which);
    }

    expect(MockClient::global()->getLastPendingRequest()?->getRequest()->resolveEndpoint())->toBe('/api/notify');
});

it('comes away with the obstacle where the core refused, or said what this app cannot read, either way', function (MockResponse $answered, KindOfObstacle $kind): void {
    foreach ([
        'the fake' => AStackThatSaysWhereAlertsGo::met(Obstacle::of($kind)),
        'the adapter' => anAlertReaderAnswered($answered),
    ] as $which => $knowing) {
        expect(whereTheyGo($knowing->whereAlertsGoOn(aStackWithAlerts(), Session::of('a-session-not-a-secret'))))->toBe($kind->name, $which);
    }
})->with([
    'refused' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'not json at all' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
    'another envelope' => [MockResponse::make((string) json_encode([...whatTheCoreSaidOfAlerts(), 'kind' => 'notify-mine'])), KindOfObstacle::AnswerCouldNotBeRead],
    'a back-end of a kind nobody reads' => [MockResponse::make((string) json_encode(whatTheCoreSaidOfAlerts(['backends' => [['id' => 'x', 'kind' => 'pigeon']]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'no unread list' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'notify', 'data' => ['state' => 'ready', 'backends' => []]])), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('stands in for a machine with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('NotifyEnvelope', whatTheCoreSaidOfAlerts()))->toBe([]);
});
