<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemovingSomebody;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;
use Modules\Kernel\Api\WhatTheRemovalFound;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Removers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatTakesThemOut;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The RemovingSomebody contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `InvitingContractTest`'s argument one conversation over:
// each act answers with a handle, and asking after the handle answers with
// the removal.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack somebody is taken out of. */
function aStackToTakeSomebodyOutOf(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session every case here asks on. */
function theSessionSomebodyIsTakenOutOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What taking anna out would cost, described and nobody taken out. */
function whatTakingAnnaOutWouldCost(): ARemoval
{
    return ARemoval::described(
        SomebodyInTheHousehold::called('Anna'),
        3,
        asksThroughTheRequestService: true,
        revoked: HowFarTheRemovalReached::Nothing,
        findings: WhatTheRemovalFound::of(),
    );
}

/** The removal both implementations answer with once the work is done. */
function theSameRemoval(): ARemoval
{
    return ARemoval::carriedOut(
        SomebodyInTheHousehold::called('Anna'),
        3,
        asksThroughTheRequestService: true,
        revoked: HowFarTheRemovalReached::MediaServerOnly,
        findings: WhatTheRemovalFound::of('The request service did not answer, so her account there is still held'),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfTheRemoval(): array
{
    return [
        'api_version' => 1,
        'kind' => 'removal',
        'data' => [
            'rehearsed' => false,
            'name' => 'Anna',
            'confirmed' => true,
            'requests' => 3,
            'asks-through-the-request-service' => true,
            'revoked' => 'media-server-only',
            'findings' => ['The request service did not answer, so her account there is still held'],
        ],
    ];
}

/**
 * Both ways of taking somebody out, each set up to answer the handle and then the removal.
 *
 * @return array<string, Closure(): RemovingSomebody>
 */
function everyWayOfTakingSomebodyOut(MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): RemovingSomebody => AStackThatTakesThemOut::answering(
            WhatBecameOfTheRemoval::underway(Job::named('j-1')),
            WhatBecameOfTheRemoval::answered(theSameRemoval()),
        ),
        'the adapter' => static function () use ($answered): RemovingSomebody {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Removers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** Everything an answer says, folded to one line, so two answers can be compared. */
function everythingTheRemovalSays(WhatBecameOfTheRemoval $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        answered: static function (ARemoval $removal): TheWordCarriedOut {
            $findings = [];

            foreach ($removal->findings() as $finding) {
                $findings[] = $finding;
            }

            return new TheWordCarriedOut(sprintf(
                '%s|%s|%d|%s|%s|%s',
                $removal->who()->name(),
                $removal->wasCarriedOut() ? 'carried out' : 'described',
                $removal->requests(),
                $removal->asksThroughTheRequestService() ? 'asks' : 'does not ask',
                $removal->revoked()->value,
                implode(',', $findings),
            ));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('takes somebody out, and follows the work to how far it reached', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 202);
    $done = MockResponse::make((string) json_encode(whatAStackSaysOfTheRemoval()));

    foreach (everyWayOfTakingSomebodyOut($handle, $done) as $which => $make) {
        $removing = $make();
        $started = $removing->remove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), ARemovalAgreed::after(whatTakingAnnaOutWouldCost()));
        $finished = $removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'));

        expect(everythingTheRemovalSays($started))->toBe('underway j-1', $which)
            ->and(everythingTheRemovalSays($finished))->toBe('Anna|carried out|3|asks|media-server-only|The request service did not answer, so her account there is still held', $which);
    }
});

it('asks what taking somebody out would cost, answered with the work to follow', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 202);

    foreach (everyWayOfTakingSomebodyOut($handle) as $which => $make) {
        expect(everythingTheRemovalSays($make()->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'))))
            ->toBe('underway j-1', $which);
    }
});

it('reads a reading nobody agreed to as described, and a removal as carried out', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 202);
    $body = whatAStackSaysOfTheRemoval();
    $body['data'] = ['name' => 'Anna', 'confirmed' => false, 'requests' => 0, 'asks-through-the-request-service' => false, 'revoked' => 'nothing', 'findings' => []];
    $removing = everyWayOfTakingSomebodyOut($handle, MockResponse::make((string) json_encode($body)))['the adapter']();
    $removing->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'));

    expect(everythingTheRemovalSays($removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))
        ->toBe('Anna|described|0|does not ask|nothing|');
});

it('sends the name without a yes for the cost and with one for the removal, each under a key of its own', function (): void {
    $sent = [];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent[] = [
                $asked->getRequest()->resolveEndpoint(),
                $asked->body()?->all(),
                $asked->headers()->get('Idempotency-Key') !== null,
            ];

            return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 202);
        },
    ]);

    $removing = new Removers(new PinnedClients(), SequencedEntropy::counting());
    $removing->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'));
    $removing->remove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), ARemovalAgreed::after(whatTakingAnnaOutWouldCost()));

    expect($sent)->toBe([
        ['/api/actions/remove', ['name' => 'anna', 'confirm' => false], true],
        ['/api/actions/remove', ['name' => 'Anna', 'confirm' => true], true],
    ]);
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        // A fault on the stack's side, even one with a sentence, is not its refusal.
        [MockResponse::make('The machine failed', 500, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all', 202), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): RemovingSomebody => AStackThatTakesThemOut::met($why),
            'the adapter' => everyWayOfTakingSomebodyOut($answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            expect(everythingTheRemovalSays($make()->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'))))
                ->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words, whenever it arrives', function (): void {
    $refused = MockResponse::make('Nobody is called bob here', 400, ['Content-Type' => 'text/plain']);
    $ways = [
        'the fake' => static fn(): RemovingSomebody => AStackThatTakesThemOut::answering(
            WhatBecameOfTheRemoval::refused('Nobody is called bob here'),
            WhatBecameOfTheRemoval::refused('Nobody is called bob here'),
            WhatBecameOfTheRemoval::refused('Nobody is called bob here'),
        ),
        'the adapter' => everyWayOfTakingSomebodyOut($refused, $refused, $refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        $removing = $make();

        expect(everythingTheRemovalSays($removing->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('bob'))))
            ->toBe('refused Nobody is called bob here', $which)
            ->and(everythingTheRemovalSays($removing->remove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), ARemovalAgreed::after(whatTakingAnnaOutWouldCost()))))
            ->toBe('refused Nobody is called bob here', $which)
            ->and(everythingTheRemovalSays($removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))
            ->toBe('refused Nobody is called bob here', $which);
    }
});

it('says the stack did not answer where a refusal carries no sentence, or the stack itself failed', function (): void {
    $silent = MockResponse::make('', 422, ['Content-Type' => 'text/plain']);
    $failed = MockResponse::make('It broke', 503, ['Content-Type' => 'text/plain']);
    $removing = everyWayOfTakingSomebodyOut($silent, $failed)['the adapter']();

    expect(everythingTheRemovalSays($removing->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value)
        ->and(everythingTheRemovalSays($removing->wouldRemove(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), SomebodyInTheHousehold::called('anna'))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('says the stack has no outcome for work it no longer knows', function (): void {
    $ways = [
        'the fake' => static fn(): RemovingSomebody => AStackThatTakesThemOut::answering(),
        'the adapter' => everyWayOfTakingSomebodyOut(MockResponse::make('{"error":"no such job"}', 404))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheRemovalSays($make()->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))->toBe('ended', $which);
    }
});

it('says work that ended before it finished has no outcome, and work still going is still underway', function (): void {
    $ended = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 200);
    $going = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'remove']]), 202);
    $removing = everyWayOfTakingSomebodyOut($ended, $going)['the adapter']();

    expect(everythingTheRemovalSays($removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))->toBe('ended')
        ->and(everythingTheRemovalSays($removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))->toBe('underway j-1');
});

it('refuses a removal whose reach is a word this app has no case for', function (): void {
    $body = whatAStackSaysOfTheRemoval();
    $body['data'] = ['name' => 'Anna', 'confirmed' => true, 'requests' => 3, 'asks-through-the-request-service' => true, 'revoked' => 'halfway', 'findings' => []];
    $removing = everyWayOfTakingSomebodyOut(MockResponse::make((string) json_encode($body)))['the adapter']();

    expect(everythingTheRemovalSays($removing->whatBecameOf(aStackToTakeSomebodyOutOf(), theSessionSomebodyIsTakenOutOn(), Job::named('j-1'))))
        ->toEqual(KindOfObstacle::AnswerCouldNotBeRead->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('RemovalEnvelope', whatAStackSaysOfTheRemoval()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
