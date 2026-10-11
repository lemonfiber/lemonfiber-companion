<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\AskingForTitles;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAnAskWent;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAnAskBecame;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Askers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatTakesAsks;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The house a member asks. */
function aHouseToAskIn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The title a member asks for. */
function aTitleToAskFor(): AFoundTitle
{
    return AFoundTitle::listed('438631', KindOfTitle::Film, 'Dune', WhenItCameOut::in(2021), WhereAFoundTitleStands::Askable);
}

/**
 * What the core answers an ask with, changed where a case says.
 *
 * @param  array<mixed> $changed
 * @return array<string, mixed>
 */
function whatTheCoreSaidOfTheAsk(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'asked', 'data' => [
        'member' => 'sanne',
        'title' => 'Dune',
        'kind' => 'movies',
        'seasons' => [],
        'released' => true,
        'outcome' => 'waiting',
        'leaves' => ['films' => 1],
        'rehearsed' => true,
        'said' => 'Asking for Dune waits for a yes, and leaves room for 1 more film this month.',
        ...$changed,
    ]];
}

/**
 * What the core refuses an ask with where the member's allowance is spent.
 *
 * @return array<string, mixed>
 */
function aRefusalOfTheAsk(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'WISH-6', 'severity' => 'warning', 'state' => 'actionable',
        'summary' => 'You have asked for all you can this month.', 'meaning' => 'Nothing was asked for.',
        'remedies' => [['action' => 'It frees up on the 1st']],
    ]];
}

/** The adapter, answered with these responses in order. */
function anAskerAnswered(MockResponse ...$answered): AskingForTitles
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Askers(new PinnedClients(), SequencedEntropy::counting());
}

/** What asking, or rehearsing, came to, as a line. */
function howTheAskWent(HowAnAskWent $went): string
{
    return $went->either(
        answered: static fn(WhatAnAskCameTo $answer): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s|%s|%s', $answer->became()->value, $answer->wasRehearsed() ? 'rehearsed' : 'asked', $answer->said())),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

function rehearsingTheAsk(AskingForTitles $asking): string
{
    return howTheAskWent($asking->rehearse(aHouseToAskIn(), Session::of('a-session-not-a-secret'), aTitleToAskFor()));
}

function askingForIt(AskingForTitles $asking, string $key = 'an-ask-key'): string
{
    return howTheAskWent($asking->ask(aHouseToAskIn(), Session::of('a-session-not-a-secret'), aTitleToAskFor(), IdempotencyKey::of($key)));
}

/**
 * Where a recorded ask went and what it carried.
 *
 * @return array{to: string, body: array<mixed>, key: mixed}
 */
function whatWasSentToAsk(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        'body' => is_array($body) ? $body : [],
        'key' => $recorded->getPendingRequest()->headers()->get('Idempotency-Key'),
    ];
}

it('reads what a rehearsal and an ask came to, either way, in the core\'s own sentence', function (): void {
    $rehearsal = WhatAnAskCameTo::answered(WhatAnAskBecame::Waiting, WhetherItWasRehearsed::Rehearsed, 'Asking for Dune waits for a yes, and leaves room for 1 more film this month.');
    $asked = WhatAnAskCameTo::answered(WhatAnAskBecame::Asked, WhetherItWasRehearsed::CarriedOut, 'Dune is asked for.');
    $fake = AStackThatTakesAsks::answering(HowAnAskWent::answered($rehearsal), HowAnAskWent::answered($asked));
    $adapter = anAskerAnswered(
        MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk())),
        MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk(['outcome' => 'asked', 'rehearsed' => false, 'said' => 'Dune is asked for.']))),
    );

    foreach (['the fake' => $fake, 'the adapter' => $adapter] as $which => $asking) {
        expect(rehearsingTheAsk($asking))->toBe('waiting|rehearsed|Asking for Dune waits for a yes, and leaves room for 1 more film this month.', $which)
            ->and(askingForIt($asking))->toBe('asked|asked|Dune is asked for.', $which);
    }
});

it('sends the rehearsal as a dry run and the ask under the key it is handed, naming the title by its id and kind and nobody by name', function (): void {
    $askers = anAskerAnswered(
        MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk())),
        MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk(['rehearsed' => false]))),
        MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk(['rehearsed' => false]))),
    );

    rehearsingTheAsk($askers);
    askingForIt($askers, 'the-same-ask');
    askingForIt($askers, 'the-same-ask');

    [$rehearsal, $first, $again] = array_map(whatWasSentToAsk(...), MockClient::global()->getRecordedResponses());

    expect($rehearsal['to'])->toBe('/api/actions/ask')
        ->and($rehearsal['body'])->toMatchArray(['id' => '438631', 'kind' => 'movies', 'dry_run' => true])
        ->and($rehearsal['body']['name'] ?? null)->toBeNull()
        ->and($first['body'])->toMatchArray(['id' => '438631', 'kind' => 'movies'])
        ->and($first['body']['dry_run'] ?? false)->toBeFalse()
        ->and($first['key'])->toBe('the-same-ask')
        ->and($again['key'])->toBe('the-same-ask')
        ->and($rehearsal['key'])->not->toBe('the-same-ask');
});

it('comes away with the obstacle, in the core\'s words where it gave any, where the core would not take the ask', function (): void {
    $went = anAskerAnswered(MockResponse::make((string) json_encode(aRefusalOfTheAsk()), 409))
        ->ask(aHouseToAskIn(), Session::of('a-session-not-a-secret'), aTitleToAskFor(), IdempotencyKey::of('k'));

    expect($went->either(
        answered: static fn(): TheWordCarriedOut => new TheWordCarriedOut('answered'),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->whatTheHouseholdWasTold()->sentence()),
    )->said)->toBe('You have asked for all you can this month.');

    foreach ([[MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused], [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead]] as [$answered, $kind]) {
        expect(askingForIt(anAskerAnswered($answered)))->toBe($kind->name);
    }
});

it('an answer this app cannot read is an answer it could not read, never a shorter one', function (array $changed): void {
    expect(askingForIt(anAskerAnswered(MockResponse::make((string) json_encode(whatTheCoreSaidOfTheAsk($changed))))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no sentence' => [['said' => null]],
    'a blank sentence' => [['said' => ' ']],
    'no outcome' => [['outcome' => null]],
    'an outcome this app has no word for' => [['outcome' => 'declined']],
    'no saying whether it was rehearsed' => [['rehearsed' => 'yes']],
]);

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AskedEnvelope', whatTheCoreSaidOfTheAsk()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aRefusalOfTheAsk()))
        ->toBe([], "The refusal this suite stands in for a stack with is not one a stack would send.\n");
});
