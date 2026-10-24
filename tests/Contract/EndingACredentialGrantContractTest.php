<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevoke;
use Modules\Kernel\Api\ACredentialRevokeAgreed;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\EndingACredentialGrant;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatARevokeCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Api\GrantEnders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatEndsGrants;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

function aStackWhoseGrantIsEnded(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function theSessionAGrantIsEndedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The grant of Plex's token to Tdarr, as this app holds it. */
function thePlexTokensGrant(): ACredentialGrant
{
    return ACredentialGrant::of(APluginId::called('tdarr'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds(''));
}

/**
 * What a stack answers ending that grant with, read or ended, with one change made to the revoke.
 *
 * @param  array<mixed>         $revoked
 * @return array<string, mixed>
 */
function whatAStackSaysOfEndingAGrant(array $revoked = []): array
{
    return ['api_version' => 1, 'kind' => 'credentials', 'data' => [
        'rehearsed' => false,
        'held' => [],
        'grants' => [['plugin' => 'tdarr', 'credential' => 'plex.token', 'of' => 'plex', 'for' => 'library-scan', 'at' => '1761321600']],
        'protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => []],
        'revoked' => [
            'agreement' => 'credentials-revoke:tdarr:plex.token:3',
            'ended' => false,
            'grant' => ['plugin' => 'tdarr', 'credential' => 'plex.token', 'of' => 'plex', 'for' => 'library-scan', 'at' => '1761321600'],
            'restarts' => ['tdarr'],
            ...$revoked,
        ],
    ]];
}

/**
 * What a stack refuses ending a grant it does not hold with.
 *
 * @return array<string, mixed>
 */
function aGrantThatIsNotHeld(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'PLUGIN-47',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'tdarr holds no grant of plex.token',
        'meaning' => 'It holds jellyfin.key.',
        'remedies' => [['action' => 'Read the credentials again']],
    ]];
}

function anEnderAnswered(MockResponse ...$answered): EndingACredentialGrant
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new GrantEnders(new PinnedClients(), SequencedEntropy::counting());
}

function aRevokeTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'credentials-revoke', 'job' => $job]]), 202);
}

/** @param WhatBecameOfTheWork<WhatARevokeCameTo> $became */
function whatEndingTheGrantCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static function (WhatARevokeCameTo $came): TheWordCarriedOut {
            $revoke = $came->revoke();
            $restarts = [];

            foreach ($revoke->restarts() as $service) {
                $restarts[] = $service->named();
            }

            return new TheWordCarriedOut(sprintf('%s %s %s:%s [%s] %s | %d grants', $revoke->wasEnded() ? 'ended' : 'read', $revoke->agreement(), $revoke->grant()->plugin()->named(), $revoke->grant()->credential(), implode(',', $restarts), $revoke->unrestarted(), count($came->held()->grants())));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

function theYesToEndingThePlexTokensGrant(): ACredentialRevokeAgreed
{
    return ACredentialRevokeAgreed::after(ACredentialRevoke::read('credentials-revoke:tdarr:plex.token:3', thePlexTokensGrant(), Services::these(ServiceId::called('tdarr'))), thePlexTokensGrant());
}

/**
 * Where a recorded request went and what it carried.
 *
 * @return array<mixed>
 */
function whatWasSentAboutAGrant(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return ['to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), ...(is_array($body) ? $body : [])];
}

it('reads ending a grant with no offer, and the yes carries the offer that reading named', function (): void {
    $enders = anEnderAnswered(aRevokeTakenOn('a'), aRevokeTakenOn('b'));

    expect(whatEndingTheGrantCameTo($enders->read(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), thePlexTokensGrant())))->toBe('following a')
        ->and(whatEndingTheGrantCameTo($enders->end(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), theYesToEndingThePlexTokensGrant())))->toBe('following b');

    [$read, $yes] = array_map(whatWasSentAboutAGrant(...), MockClient::global()->getRecordedResponses());

    expect($read)->toMatchArray(['to' => '/api/actions/credentials-revoke', 'plugin' => 'tdarr', 'credential' => 'plex.token'])
        ->and($read['offer'] ?? null)->toBeNull()
        ->and($read['dry_run'] ?? false)->toBeFalse()
        ->and($yes)->toMatchArray(['to' => '/api/actions/credentials-revoke', 'plugin' => 'tdarr', 'credential' => 'plex.token', 'offer' => 'credentials-revoke:tdarr:plex.token:3']);
});

it('reads what the work came to: the reading, and the grant ended with why its services did not restart', function (): void {
    $read = MockResponse::make((string) json_encode(whatAStackSaysOfEndingAGrant()));
    $ended = MockResponse::make((string) json_encode(whatAStackSaysOfEndingAGrant(['ended' => true, 'unrestarted' => 'It was stopped'])));

    expect(whatEndingTheGrantCameTo(anEnderAnswered($read)->whatBecameOf(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), Job::named('a'))))
        ->toBe('read credentials-revoke:tdarr:plex.token:3 tdarr:plex.token [tdarr]  | 1 grants')
        ->and(whatEndingTheGrantCameTo(anEnderAnswered($ended)->whatBecameOf(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), Job::named('b'))))
        ->toBe('ended credentials-revoke:tdarr:plex.token:3 tdarr:plex.token [tdarr] It was stopped | 1 grants');
});

it('reads the stack refusing a grant it does not hold in its own words, and a job it no longer has as ended', function (): void {
    $refused = MockResponse::make((string) json_encode(aGrantThatIsNotHeld()), 400);

    expect(whatEndingTheGrantCameTo(anEnderAnswered($refused)->end(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), theYesToEndingThePlexTokensGrant())))
        ->toBe('refused: tdarr holds no grant of plex.token / Read the credentials again')
        ->and(whatEndingTheGrantCameTo(anEnderAnswered(MockResponse::make('{"error":"no such job"}', 404))->whatBecameOf(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), Job::named('a'))))
        ->toBe('ended');
});

it('reads work that came back unreadable as an answer it could not read', function (array $revoked): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfEndingAGrant($revoked)));

    expect(whatEndingTheGrantCameTo(anEnderAnswered($finished)->whatBecameOf(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), Job::named('a'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no word on whether it ended' => [['ended' => 'perhaps']],
    'a grant of nothing' => [['grant' => ['plugin' => 'tdarr', 'credential' => ' ', 'of' => 'plex', 'for' => 'library-scan']]],
    'a restart named blank' => [['restarts' => [' ']]],
    'no word of the grant at all' => [['agreement' => null]],
]);

it('the fake remembers each reading, each yes and each handle followed', function (): void {
    $ending = AStackThatEndsGrants::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    $ending->read(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), thePlexTokensGrant());
    $ending->end(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), theYesToEndingThePlexTokensGrant());
    $became = $ending->whatBecameOf(aStackWhoseGrantIsEnded(), theSessionAGrantIsEndedWith(), Job::named(AStackThatEndsGrants::THE_END));

    expect($ending->asked())->toBe(['read:tdarr:plex.token', 'end:tdarr:plex.token:credentials-revoke:tdarr:plex.token:3', sprintf('after:%s', AStackThatEndsGrants::THE_END)])
        ->and(whatEndingTheGrantCameTo($became))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', whatAStackSaysOfEndingAGrant()))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', whatAStackSaysOfEndingAGrant(['ended' => true, 'unrestarted' => 'It was stopped'])))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aGrantThatIsNotHeld()))->toBe([]);
});
