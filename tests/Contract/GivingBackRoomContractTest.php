<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACategoryOffered;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARoomGivenBackAgreed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\GivingBackRoom;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheRoomGivenBack;
use Modules\Kernel\Api\TheRoomOnOffer;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhyItemsStayed;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\RoomGivers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatGivesBackRoom;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

function aStackGivingBackRoom(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

function theSessionRoomIsGivenBackWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

function theYesToGivingBackWhatNothingTook(): ARoomGivenBackAgreed
{
    return ARoomGivenBackAgreed::to(
        TheRoomOnOffer::named('space-3f9a', ACategoryOffered::holding(WhatALineIsAbout::Orphaned, WhatItOccupies::counted(30, 30), 12)),
        WhatALineIsAbout::Orphaned,
    );
}

/**
 * What a finished cleanup answers with, its `reclaimed` as given.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfGivingBackRoom(mixed $reclaimed): array
{
    return ['api_version' => 1, 'kind' => 'space', 'data' => [
        'rehearsed' => false,
        'volumes' => [],
        'level' => 'warning',
        'halted' => false,
        'consumption' => [],
        'reclaimable' => [['category' => ['of' => 'orphaned'], 'reclaim' => 'the_easy_win', 'tally' => ['files' => 12, 'logical' => 30, 'physical' => 30, 'shared' => 0]]],
        'candidates' => [],
        'outsized' => [],
        'interrupted' => [],
        'agreement' => 'space-3f9a',
        'reclaimed' => $reclaimed,
    ]];
}

/**
 * What a stack refuses a yes with, under a code.
 *
 * @return array<string, mixed>
 */
function aGivingBackRefusedWith(string $code, string $summary): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => $code,
        'severity' => 'error',
        'state' => 'guided',
        'summary' => $summary,
        'meaning' => 'The disk has changed since it was read.',
        'remedies' => [['action' => 'Read the offer again, and answer the name it prints']],
    ]];
}

function aRoomGiverAnswered(MockResponse ...$answered): GivingBackRoom
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new RoomGivers(new PinnedClients(), SequencedEntropy::counting());
}

/** @param WhatBecameOfTheWork<TheRoomGivenBack> $became */
function whatGivingBackRoomCameTo(WhatBecameOfTheWork $became): string
{
    return $became->eitherOrMoved(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(TheRoomGivenBack $back): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d back, %d went, left: %s', $back->bytes(), $back->went(), implode(' / ', iterator_to_array($back->left(), preserve_keys: false)))),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
        moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved: %s / %s', $why->summary(), $why->remedy())),
    )->said;
}

/**
 * Where a recorded request went and what it carried.
 *
 * @return array<mixed>
 */
function whatWasSentToGiveBackRoom(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return ['to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), ...(is_array($body) ? $body : [])];
}

it('sends the yes with the offer\'s name and the one category agreed to, and follows the handle it answers', function (): void {
    $taken = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'space', 'job' => 'a']]), 202);

    expect(whatGivingBackRoomCameTo(aRoomGiverAnswered($taken)->giveBack(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), theYesToGivingBackWhatNothingTook())))->toBe('following a');

    [$yes] = array_map(whatWasSentToGiveBackRoom(...), MockClient::global()->getRecordedResponses());

    expect($yes)->toBe(['to' => '/api/actions/space', 'only' => ['orphaned'], 'offer' => 'space-3f9a']);
});

it('reads what the cleanup came to, and a handle the stack no longer knows as ended', function (): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfGivingBackRoom(['bytes' => 30, 'gone' => ['/srv/downloads/a'], 'left' => [['at' => '/srv/downloads/b', 'why' => 'Permission denied']], 'rehearsed' => false])));

    expect(whatGivingBackRoomCameTo(aRoomGiverAnswered($finished)->whatBecameOf(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), Job::named('a'))))->toBe('30 back, 1 went, left: Permission denied')
        ->and(whatGivingBackRoomCameTo(aRoomGiverAnswered(MockResponse::make('{"error":"no such job"}', 404))->whatBecameOf(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), Job::named('a'))))->toBe('ended');
});

it('reads a yes refused because the offer moved as moved, and any other refusal as refused', function (): void {
    expect(whatGivingBackRoomCameTo(aRoomGiverAnswered(MockResponse::make((string) json_encode(aGivingBackRefusedWith('SPACE-6', 'That answer was given for a different reading of the disk')), 400))->giveBack(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), theYesToGivingBackWhatNothingTook())))
        ->toBe('moved: That answer was given for a different reading of the disk / Read the offer again, and answer the name it prints')
        ->and(whatGivingBackRoomCameTo(aRoomGiverAnswered(MockResponse::make((string) json_encode(aGivingBackRefusedWith('SPACE-8', 'That is not on offer')), 400))->giveBack(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), theYesToGivingBackWhatNothingTook())))
        ->toBe('refused: That is not on offer');
});

it('reads a cleanup that came back unreadable as an answer it could not read', function (mixed $reclaimed): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfGivingBackRoom($reclaimed)));

    expect(whatGivingBackRoomCameTo(aRoomGiverAnswered($finished)->whatBecameOf(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), Job::named('a'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'nothing reclaimed' => [null],
    'an item left with no reason' => [['bytes' => 0, 'gone' => [], 'left' => [['at' => '/srv/downloads/b', 'why' => '']], 'rehearsed' => false]],
]);

it('the fake remembers each yes and each handle followed', function (): void {
    $giving = AStackThatGivesBackRoom::answering(WhatBecameOfTheWork::answered(TheRoomGivenBack::reported(30, 1, WhyItemsStayed::because())));

    expect(whatGivingBackRoomCameTo($giving->giveBack(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), theYesToGivingBackWhatNothingTook())))->toBe(sprintf('following %s', AStackThatGivesBackRoom::THE_GIVING_BACK))
        ->and(whatGivingBackRoomCameTo($giving->whatBecameOf(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), Job::named(AStackThatGivesBackRoom::THE_GIVING_BACK))))->toBe('30 back, 1 went, left: ')
        ->and($giving->asked())->toBe(['give-back:space-3f9a:orphaned', sprintf('after:%s', AStackThatGivesBackRoom::THE_GIVING_BACK)])
        ->and(whatGivingBackRoomCameTo(AStackThatGivesBackRoom::untouched()->giveBack(aStackGivingBackRoom(), theSessionRoomIsGivenBackWith(), theYesToGivingBackWhatNothingTook())))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SpaceEnvelope', whatAStackSaysOfGivingBackRoom(['bytes' => 30, 'gone' => ['/srv/downloads/a'], 'left' => [['at' => '/srv/downloads/b', 'why' => 'Permission denied']], 'rehearsed' => false])))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aGivingBackRefusedWith('SPACE-6', 'That answer was given for a different reading of the disk')))->toBe([]);
});
