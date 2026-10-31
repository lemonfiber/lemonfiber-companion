<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\ACertificateReplacementAgreed;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnInternalCertificate;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReplacingTheInternalCertificate;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Sdk\Api\CertificateReplacers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatReplacesItsCertificate;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

function aStackWhoseCertificateIsReplaced(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('r', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

function theSessionACertificateIsReplacedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What a stack answers replacing its internal certificate with, with one change made to `rotated`.
 *
 * @param  array<mixed>         $rotated
 * @return array<string, mixed>
 */
function whatAStackSaysOfReplacingItsCertificate(array $rotated = []): array
{
    return ['api_version' => 1, 'kind' => 'credentials', 'data' => [
        'rehearsed' => false,
        'held' => [[
            'name' => 'Media server internal certificate',
            'state' => 'active',
            'origin' => 'lemonfiber',
            'consumers' => ['the decline service', 'the request gate'],
            'from' => ['origin' => 'bundled'],
            'location' => '/srv/jellyfin/internal.p12',
            'setting' => AnInternalCertificate::SETTING,
            'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        ]],
        'grants' => [],
        'protection' => ['summary' => 'Plain files', 'against' => [], 'not_against' => []],
        'rotated' => [
            'credential' => 'Media server internal certificate',
            'consumers' => [],
            'offer' => 'the-offer',
            'settled' => ['settled' => 'rehearsed', 'detail' => 'the decline service and the request gate are handed the new SHA-256; jellyfin then serves it and may restart', 'location' => '/srv/jellyfin/internal.p12', 'afterwards' => []],
            ...$rotated,
        ],
    ]];
}

/**
 * What a stack refuses a yes with, under a code.
 *
 * @return array<string, mixed>
 */
function aReplacementRefusedWith(string $code, string $summary): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => $code,
        'severity' => 'warning',
        'state' => 'guided',
        'summary' => $summary,
        'meaning' => 'The media server has changed since it was read.',
        'remedies' => [['action' => 'Read the replacement again, and answer the offer it gives now.']],
    ]];
}

function aReplacerAnswered(MockResponse ...$answered): ReplacingTheInternalCertificate
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new CertificateReplacers(new PinnedClients(), SequencedEntropy::counting());
}

function aReplacementTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'internal-certificate-replace', 'job' => $job]]), 202);
}

/** @param WhatBecameOfTheWork<WhatAReplacementCameTo> $became */
function whatReplacingTheCertificateCameTo(WhatBecameOfTheWork $became): string
{
    return $became->eitherOrMoved(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static function (WhatAReplacementCameTo $came): TheWordCarriedOut {
            $replacement = $came->replacement();

            return new TheWordCarriedOut(sprintf('%s %s | %s | %d held', $replacement->settledAt()->value, $replacement->agreement(), $replacement->said(), count($came->held())));
        },
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
        moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved: %s / %s', $why->summary(), $why->remedy())),
    )->said;
}

function theYesToReplacingTheCertificate(): ACertificateReplacementAgreed
{
    return ACertificateReplacementAgreed::after(ACertificateReplacement::read('the-offer', 'the decline service is handed it'));
}

/**
 * Where a recorded request went and what it carried.
 *
 * @return array<mixed>
 */
function whatWasSentAboutTheCertificate(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return ['to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), ...(is_array($body) ? $body : [])];
}

it('reads replacing the certificate with no offer, and the yes carries the offer that reading named and nothing else', function (): void {
    $replacers = aReplacerAnswered(aReplacementTakenOn('a'), aReplacementTakenOn('b'));

    expect(whatReplacingTheCertificateCameTo($replacers->read(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith())))->toBe('following a')
        ->and(whatReplacingTheCertificateCameTo($replacers->replace(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), theYesToReplacingTheCertificate())))->toBe('following b');

    [$read, $yes] = array_map(whatWasSentAboutTheCertificate(...), MockClient::global()->getRecordedResponses());

    expect($read['to'])->toBe('/api/actions/internal-certificate-replace')
        ->and($read['offer'] ?? null)->toBeNull()
        ->and($yes)->toBe(['to' => '/api/actions/internal-certificate-replace', 'offer' => 'the-offer']);
});

it('reads what the work came to: the reading under its offer, and where the replacement settled', function (): void {
    $read = MockResponse::make((string) json_encode(whatAStackSaysOfReplacingItsCertificate()));
    $replaced = MockResponse::make((string) json_encode(whatAStackSaysOfReplacingItsCertificate(['offer' => null, 'settled' => ['settled' => 'replaced', 'observed' => 'jellyfin serves the new certificate']])));

    expect(whatReplacingTheCertificateCameTo(aReplacerAnswered($read)->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named('a'))))
        ->toBe('rehearsed the-offer | the decline service and the request gate are handed the new SHA-256; jellyfin then serves it and may restart | 1 held')
        ->and(whatReplacingTheCertificateCameTo(aReplacerAnswered($replaced)->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named('b'))))
        ->toBe('replaced  | jellyfin serves the new certificate | 1 held');
});

it('reads a yes refused because the reading moved as moved, wherever the stack refuses it, and any other refusal as refused', function (): void {
    $moved = static fn(): MockResponse => MockResponse::make((string) json_encode(aReplacementRefusedWith('CRED-4', 'What was agreed to is not what replacing the certificate would do now')), 400);

    expect(whatReplacingTheCertificateCameTo(aReplacerAnswered($moved())->replace(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), theYesToReplacingTheCertificate())))
        ->toBe('moved: What was agreed to is not what replacing the certificate would do now / Read the replacement again, and answer the offer it gives now.')
        ->and(whatReplacingTheCertificateCameTo(aReplacerAnswered($moved())->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named('a'))))
        ->toBe('moved: What was agreed to is not what replacing the certificate would do now / Read the replacement again, and answer the offer it gives now.')
        ->and(whatReplacingTheCertificateCameTo(aReplacerAnswered(MockResponse::make((string) json_encode(aReplacementRefusedWith('CRED-1', 'Nothing was replaced')), 400))->replace(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), theYesToReplacingTheCertificate())))
        ->toBe('refused: Nothing was replaced')
        ->and(whatReplacingTheCertificateCameTo(aReplacerAnswered(MockResponse::make('{"error":"no such job"}', 404))->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named('a'))))
        ->toBe('ended');
});

it('reads work that came back unreadable as an answer it could not read', function (array $rotated): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfReplacingItsCertificate($rotated)));

    expect(whatReplacingTheCertificateCameTo(aReplacerAnswered($finished)->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named('a'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no word of where it settled' => [['settled' => null]],
    'a word it has no case for' => [['settled' => ['settled' => 'half-done']]],
    'a holder with no name' => [['settled' => ['settled' => 'refused', 'detail' => 'no'], 'consumers' => [['consumer' => '', 'reach' => ['reach' => 'updated']]]]],
]);

it('the fake remembers each reading, each yes and each handle followed', function (): void {
    $replacing = AStackThatReplacesItsCertificate::answering(WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    $replacing->read(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith());
    $replacing->replace(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), theYesToReplacingTheCertificate());
    $became = $replacing->whatBecameOf(aStackWhoseCertificateIsReplaced(), theSessionACertificateIsReplacedWith(), Job::named(AStackThatReplacesItsCertificate::THE_REPLACEMENT));

    expect($replacing->asked())->toBe(['read', 'replace:the-offer', sprintf('after:%s', AStackThatReplacesItsCertificate::THE_REPLACEMENT)])
        ->and(whatReplacingTheCertificateCameTo($became))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', whatAStackSaysOfReplacingItsCertificate()))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', whatAStackSaysOfReplacingItsCertificate(['offer' => null, 'settled' => ['settled' => 'replaced', 'observed' => 'jellyfin serves the new certificate']])))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aReplacementRefusedWith('CRED-4', 'What was agreed to is not what replacing the certificate would do now')))->toBe([]);
});
