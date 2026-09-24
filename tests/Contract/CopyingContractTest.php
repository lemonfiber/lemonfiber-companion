<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCopies;
use Modules\Sdk\Api\Copyists;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatListsItsCopies;
use Tests\Support\WhatTheContractAccepts;

// The Copying contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `StoringContractTest`'s argument for the other reading on
// the same screen.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked which copies it holds. */
function aStackThatHoldsCopies(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/**
 * The payload a stack sends listing these copies.
 *
 * @param  list<mixed>          $archives
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsCopies(array $archives): array
{
    return ['api_version' => 1, 'kind' => 'archives', 'data' => ['archives' => $archives]];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Copying>
 */
function everyWayOfAskingForTheCopies(MockResponse $answered, TheCopies|Obstacle $answer): array
{
    return [
        'the fake' => static fn(): Copying => $answer instanceof Obstacle
            ? AStackThatListsItsCopies::met($answer)
            : AStackThatListsItsCopies::with($answer),
        'the adapter' => static function () use ($answered): Copying {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Copyists(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheCopiesTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheCopiesSay(Copying $copying): string
{
    return $copying->copiesOn(aStackThatHoldsCopies(), Session::of('a-session-not-a-secret'))->either(
        copies: static function (TheCopies $copies): WhatTheCopiesTurnedOutToSay {
            $names = [];

            foreach ($copies as $name) {
                $names[] = $name;
            }

            return new WhatTheCopiesTurnedOutToSay(sprintf('%d: %s', count($copies), implode(', ', $names)));
        },
        met: static fn(Obstacle $why): WhatTheCopiesTurnedOutToSay => new WhatTheCopiesTurnedOutToSay($why->value),
    )->said;
}

it('comes away with each copy by its name, in the stack\'s order', function (): void {
    $names = ['lemonfiber-20260924-0300-full', 'lemonfiber-20260923-0300-full'];
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsCopies($names)));

    foreach (everyWayOfAskingForTheCopies($answered, TheCopies::named(...$names)) as $which => $make) {
        expect(everythingTheCopiesSay($make()))->toBe('2: lemonfiber-20260924-0300-full, lemonfiber-20260923-0300-full', $which);
    }
});

it('N6-R9 — an empty list is an answer, and one that could not be read is not', function (): void {
    $empty = MockResponse::make((string) json_encode(whatAStackSaysOfItsCopies([])));

    foreach (everyWayOfAskingForTheCopies($empty, TheCopies::named()) as $which => $make) {
        expect(everythingTheCopiesSay($make()))->toBe('0: ', $which);
    }

    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForTheCopies($answered, $why) as $which => $make) {
            expect(everythingTheCopiesSay($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N6-R9 — a copy with no name is an obstacle, never a shorter list', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsCopies(['lemonfiber-20260924-0300-full', ''])))]);

    expect(everythingTheCopiesSay(new Copyists(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ArchivesEnvelope', whatAStackSaysOfItsCopies(['lemonfiber-20260924-0300-full'])))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
