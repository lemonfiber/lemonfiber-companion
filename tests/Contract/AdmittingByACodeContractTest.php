<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AdmittingByACode;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\CodeAsked;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheCodeStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\Admissions;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PinnedDoors;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\ADoorThatGivesCodes;
use Tests\Support\TheWordCarriedOut;

// The AdmittingByACode contract, run against the adapter and against the fake,
// each driven to the same answer from the same far end.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

const A_CODES_LAPSE = 1_789_380_000;

const A_CODES_HANDLE = 'a-handle-nobody-sees';

function aHouseThatSignsInByCode(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', 64)),
    );
}

function theCodeIssued(): ACodeToApprove
{
    return ACodeToApprove::issued('ABCD', 'https://approve.example/link', Instant::atEpochSeconds(A_CODES_LAPSE), AWaitingCode::answered(A_CODES_HANDLE));
}

function anAnswerOfKind(string $kind, string $data, int $status = 200): MockResponse
{
    return MockResponse::make(sprintf('{"api_version":1,"kind":"%s","data":{%s}}', $kind, $data), $status);
}

function aRefusalOfTheCode(string $code, int $status, string $summary = 'The house said no.'): MockResponse
{
    return MockResponse::make(sprintf(
        '{"api_version":1,"kind":"error","data":{"code":"%s","summary":"%s","meaning":"m","severity":"error","state":"actionable","remedies":[{"action":"Do this."}]}}',
        $code,
        $summary,
    ), $status);
}

function theCodeAdapterAnswering(MockResponse ...$answered): AdmittingByACode
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Admissions(new PinnedDoors(), new PinnedClients());
}

/** What asking for a code came to, as words. */
function whatAskingCameTo(AdmittingByACode $door): string
{
    return $door->askForACode(aHouseThatSignsInByCode())->either(
        issued: static fn(ACodeToApprove $code): TheWordCarriedOut => new TheWordCarriedOut(sprintf('issued %s at %s until %d', $code->code, $code->approveAt->url(), $code->lapses->epochSeconds())),
        byPassword: static fn(WhatTheHouseholdWasTold $told): TheWordCarriedOut => new TheWordCarriedOut(sprintf('by password: %s / %s', $told->sentence(), $told->remedy())),
        refused: static fn(Obstacle $why, WhenToTryAgain $tryAgain): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s %s', $why->kind()->value, $tryAgain->isSaid() ? $tryAgain->wait()->inSeconds() : 'unsaid')),
    )->said;
}

/** What finishing the code came to, as words. */
function whatFinishingCameTo(AdmittingByACode $door): string
{
    return $door->finishByCode(aHouseThatSignsInByCode(), AWaitingCode::answered(A_CODES_HANDLE))->either(
        opened: static fn(Session $session, Instant $until, Whose $whose): TheWordCarriedOut => new TheWordCarriedOut(sprintf('opened %s until %d for %s', $session->forTheHeader(), $until->epochSeconds(), $whose->either(operator: static fn(): TheWordCarriedOut => new TheWordCarriedOut('the operator'), member: static fn(string $member): TheWordCarriedOut => new TheWordCarriedOut($member))->said)),
        waiting: static fn(Instant $lapses): TheWordCarriedOut => new TheWordCarriedOut(sprintf('waiting until %d', $lapses->epochSeconds())),
        lapsed: static fn(WhatTheHouseholdWasTold $told): TheWordCarriedOut => new TheWordCarriedOut(sprintf('lapsed: %s', $told->sentence())),
        refused: static fn(Obstacle $why, WhenToTryAgain $tryAgain): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s %s', $why->kind()->value, $tryAgain->isSaid() ? $tryAgain->wait()->inSeconds() : 'unsaid')),
    )->said;
}

it('comes away with the code, where it is approved and when it lapses', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(anAnswerOfKind('sign-in-code', sprintf('"code":"ABCD","approve_at":"https://approve.example/link","lapses_at":%d,"waiting":"%s"', A_CODES_LAPSE, A_CODES_HANDLE))),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued()),
    ];

    foreach ($doors as $which => $door) {
        expect(whatAskingCameTo($door))->toBe(sprintf('issued ABCD at https://approve.example/link until %d', A_CODES_LAPSE), $which);
    }
});

it('hands on the house\'s words where it signs in by password', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(aRefusalOfTheCode('ADMIT-17', 409, 'This house signs in with a password.')),
        'the fake' => ADoorThatGivesCodes::answering(CodeAsked::byPassword(WhatTheHouseholdWasTold::said('This house signs in with a password.', 'Do this.'))),
    ];

    foreach ($doors as $which => $door) {
        expect(whatAskingCameTo($door))->toBe('by password: This house signs in with a password. / Do this.', $which);
    }
});

it('says how long to wait where too many codes were asked for', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(MockResponse::make('{"error":"wait"}', 429, ['Retry-After' => '60'])),
        'the fake' => ADoorThatGivesCodes::answering(CodeAsked::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), WhenToTryAgain::after(HowLong::ofSeconds(60)))),
    ];

    foreach ($doors as $which => $door) {
        expect(whatAskingCameTo($door))->toBe('refused too_many_attempts 60', $which);
    }
});

it('says what stood in the way of asking where the house did not answer, or answered a code it could not show', function (): void {
    $unanswered = whatAskingCameTo(theCodeAdapterAnswering(MockResponse::make()->throw(static fn(): Unreachable => Unreachable::whenAsking('/api/session/code', 'Connection refused'))));

    expect($unanswered)->toStartWith('refused ')
        ->and($unanswered)->not->toStartWith('refused too_many_attempts')
        ->and(whatAskingCameTo(theCodeAdapterAnswering(anAnswerOfKind('sign-in-code', sprintf('"code":" ","approve_at":"https://approve.example/link","lapses_at":%d,"waiting":"%s"', A_CODES_LAPSE, A_CODES_HANDLE)))))->toBe(sprintf('refused %s unsaid', KindOfObstacle::AnswerCouldNotBeRead->value))
        ->and(whatAskingCameTo(ADoorThatGivesCodes::answering(CodeAsked::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer), WhenToTryAgain::unsaid()))))->toBe(sprintf('refused %s unsaid', KindOfObstacle::StackDidNotAnswer->value));
});

it('says the code still waits, and when it lapses', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(anAnswerOfKind('sign-in-code', sprintf('"lapses_at":%d,"waiting":"%s"', A_CODES_LAPSE, A_CODES_HANDLE))),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued()),
    ];

    foreach ($doors as $which => $door) {
        expect(whatFinishingCameTo($door))->toBe(sprintf('waiting until %d', A_CODES_LAPSE), $which);
    }
});

it('comes away with the member\'s session once the code was approved', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(anAnswerOfKind('admission', '"token":"a-session-not-a-secret","until":1789380000,"member":"robin"')),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued(), HowTheCodeStands::opened(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(A_CODES_LAPSE), Whose::member('robin'))),
    ];

    foreach ($doors as $which => $door) {
        expect(whatFinishingCameTo($door))->toBe(sprintf('opened a-session-not-a-secret until %d for robin', A_CODES_LAPSE), $which);
    }
});

it('hands on the house\'s words where it no longer waits on the code, never as a wrong password', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(aRefusalOfTheCode('ADMIT-15', 401, 'That code lapsed.')),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued(), HowTheCodeStands::lapsed(WhatTheHouseholdWasTold::said('That code lapsed.', 'Do this.'))),
    ];

    foreach ($doors as $which => $door) {
        expect(whatFinishingCameTo($door))->toBe('lapsed: That code lapsed.', $which);
    }
});

it('reads a code the house no longer waits on, or a house that signs in by password, by its code even where it said nothing', function (): void {
    expect(whatFinishingCameTo(theCodeAdapterAnswering(aRefusalOfTheCode('ADMIT-15', 401, ''))))->toBe('lapsed: ')
        ->and(whatAskingCameTo(theCodeAdapterAnswering(aRefusalOfTheCode('ADMIT-17', 409, ''))))->toBe('by password:  / ');
});

it('says what stood in the way of finishing where the house did not answer', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(MockResponse::make('{"error":"gone"}', 500)),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued(), HowTheCodeStands::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer), WhenToTryAgain::unsaid())),
    ];

    foreach ($doors as $which => $door) {
        expect(whatFinishingCameTo($door))->toBe(sprintf('refused %s unsaid', KindOfObstacle::StackDidNotAnswer->value), $which);
    }
});

it('says how long to wait where the house is asked too often whether the code was approved', function (): void {
    $doors = [
        'the adapter' => theCodeAdapterAnswering(MockResponse::make('{"error":"wait"}', 429, ['Retry-After' => '30'])),
        'the fake' => ADoorThatGivesCodes::issuing(theCodeIssued(), HowTheCodeStands::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), WhenToTryAgain::after(HowLong::ofSeconds(30)))),
    ];

    foreach ($doors as $which => $door) {
        expect(whatFinishingCameTo($door))->toBe('refused too_many_attempts 30', $which);
    }
});

it('the fake counts asks and finishes, and keeps the handle the last finish carried', function (): void {
    $door = ADoorThatGivesCodes::issuing(theCodeIssued());
    $door->askForACode(aHouseThatSignsInByCode());
    whatFinishingCameTo($door);
    whatFinishingCameTo($door);

    expect([$door->asks(), $door->finishes(), $door->finishedWith()])->toBe([1, 2, A_CODES_HANDLE]);
});
