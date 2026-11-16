<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\APartTheWordsLeaveOut;
use Modules\Kernel\Api\AskingHowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePartsTheWordsLeaveOut;
use Modules\Kernel\Api\WhoseStackItIs;
use Modules\Sdk\Api\Foundations;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysHowItRunsWithout;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The AskingHowItRunsWithoutLemonfiber contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked how it runs without lemonfiber. */
function aStackThatRunsWithout(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with: the operator's own stack, with one part the words leave out. */
function theSameFoundations(): HowItRunsWithoutLemonfiber
{
    return HowItRunsWithoutLemonfiber::said(
        WhoseStackItIs::theOperators('/home/anna/stack'),
        '/home/anna/stack',
        '/home/anna/stack/.env',
        TheCommandLine::of('docker', 'compose', '--project-directory', '/home/anna/stack', 'up', '-d'),
        'lemonfiber --yes --no-input',
        ThePartsTheWordsLeaveOut::these(APartTheWordsLeaveOut::because('gluetun', 'It needs the VPN key from my password manager first')),
    );
}

/**
 * The payload a stack sends for that stack.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfRunningWithout(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'stack',
        'data' => [
            'source' => ['own' => '/home/anna/stack'],
            'project' => '/home/anna/stack',
            'env' => '/home/anna/stack/.env',
            'plain_up' => ['docker', 'compose', '--project-directory', '/home/anna/stack', 'up', '-d'],
            'non_interactive' => ['form' => 'lemonfiber --yes --no-input'],
            'exceptions' => [['part' => 'gluetun', 'because' => 'It needs the VPN key from my password manager first']],
            ...$changed,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): AskingHowItRunsWithoutLemonfiber>
 */
function everyWayOfAskingHowItRunsWithout(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): AskingHowItRunsWithoutLemonfiber => $why instanceof Obstacle
            ? AStackThatSaysHowItRunsWithout::met($why)
            : AStackThatSaysHowItRunsWithout::with(theSameFoundations()),
        'the adapter' => static function () use ($answered): AskingHowItRunsWithoutLemonfiber {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Foundations(new PinnedClients());
        },
    ];
}

/** Everything one way of asking comes away with, folded to text. */
function everythingTheStackSaysOfRunningWithout(AskingHowItRunsWithoutLemonfiber $asking): string
{
    return $asking->withoutLemonfiber(aStackThatRunsWithout(), Session::of('a-session-not-a-secret'))->either(
        said: static function (HowItRunsWithoutLemonfiber $how): TheWordCarriedOut {
            $leftOut = [];

            foreach ($how->leftOut() as $part) {
                $leftOut[] = sprintf('%s (%s)', $part->part(), $part->reason());
            }

            return new TheWordCarriedOut(sprintf(
                '%s "%s"; project %s; env %s; up "%s"; asks nothing with "%s"; leaves out [%s]',
                $how->whose()->isTheOperators() ? 'own' : 'bundled',
                $how->whose()->directory(),
                $how->project(),
                $how->env(),
                $how->plainUp()->asTyped(),
                $how->nonInteractive(),
                implode(', ', $leftOut),
            ));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

it('comes away with whose stack it is, where it lives, the words that bring it up, how an action asks nothing, and what the words leave out', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfRunningWithout()));
    $said = [];

    foreach (everyWayOfAskingHowItRunsWithout($answered) as $which => $make) {
        $said[$which] = everythingTheStackSaysOfRunningWithout($make());
    }

    expect($said['the adapter'])->toBe($said['the fake'])
        ->and($said['the fake'])->toBe('own "/home/anna/stack"; project /home/anna/stack; env /home/anna/stack/.env; up "docker compose --project-directory /home/anna/stack up -d"; asks nothing with "lemonfiber --yes --no-input"; leaves out [gluetun (It needs the VPN key from my password manager first)]');
});

it('reads the bundled stack, with nothing left out', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfRunningWithout(['source' => 'bundled', 'exceptions' => []])))]);

    expect(everythingTheStackSaysOfRunningWithout(new Foundations(new PinnedClients())))
        ->toBe('bundled ""; project /home/anna/stack; env /home/anna/stack/.env; up "docker compose --project-directory /home/anna/stack up -d"; asks nothing with "lemonfiber --yes --no-input"; leaves out []');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingHowItRunsWithout($answered, $why) as $which => $make) {
            expect(everythingTheStackSaysOfRunningWithout($make()))->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

// `stack` is stood in for and not judged: each case is a payload the contract does not describe, which no stack sends.
it('refuses what it cannot read, rather than drawing the operator\'s own stack as the bundled one', function (array $changed): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfRunningWithout($changed)))]);

    expect(everythingTheStackSaysOfRunningWithout(new Foundations(new PinnedClients())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->value);
})->with([
    'a source it has no word for' => [['source' => 'borrowed']],
    'an own stack with no directory' => [['source' => ['own' => '']]],
    'no source at all' => [['source' => null]],
    'no project' => [['project' => null]],
    'no env' => [['env' => 7]],
    'no words to bring it up' => [['plain_up' => []]],
    'a word that is not a word' => [['plain_up' => ['docker', 7]]],
    'words that are not a list' => [['plain_up' => 'docker compose up']],
    'no way to ask nothing' => [['non_interactive' => 'lemonfiber --yes']],
    'a way to ask nothing with no words' => [['non_interactive' => ['form' => '']]],
    'no exceptions' => [['exceptions' => null]],
    'an exception that is not an object' => [['exceptions' => ['gluetun']]],
    'an exception with no reason' => [['exceptions' => [['part' => 'gluetun']]]],
    'an exception with no part' => [['exceptions' => [['because' => 'b']]]],
]);

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StackEnvelope', whatAStackSaysOfRunningWithout()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('StackEnvelope', whatAStackSaysOfRunningWithout(['source' => 'bundled'])))
        ->toBe([], "The bundled payload this suite stands in for a stack with is not one a stack would send.\n");
});
