<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingBeside;
use Modules\Kernel\Api\SomethingKept;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\TheRoots;
use Modules\Kernel\Api\WhatIsBeside;
use Modules\Kernel\Api\WhatIsKept;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Kernel\Api\WhereThingsAreKept;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Storekeepers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatItKeeps;
use Tests\Support\WhatTheContractAccepts;

// The Storing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `RationingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked what it keeps. */
function aStackThatKeepsThings(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameThingsKept(): WhatThisMachineKeeps
{
    return WhatThisMachineKeeps::of(
        TheRoots::of(WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes')),
        WhatIsKept::of(
            SomethingKept::kept('The VPN credentials', '/srv/lemonfiber/secrets/vpn', 'So the tunnel can be raised', WhetherItHoldsASecret::Secret),
            SomethingKept::kept('Sonarr\'s settings', '/srv/lemonfiber/config/sonarr', 'So Sonarr starts as it was left', WhetherItHoldsASecret::Plain),
        ),
        WhatIsBeside::of(SomethingBeside::named('/srv/media', 'Your library, which the stack reads and never removes')),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysItKeeps(mixed $secret = true): array
{
    return [
        'api_version' => 1,
        'kind' => 'stored',
        'data' => [
            'roots' => [['at' => '/srv/lemonfiber', 'what' => 'Everything the stack writes']],
            'kept' => [
                ['what' => 'The VPN credentials', 'at' => '/srv/lemonfiber/secrets/vpn', 'why' => 'So the tunnel can be raised', 'secret' => $secret],
                ['what' => 'Sonarr\'s settings', 'at' => '/srv/lemonfiber/config/sonarr', 'why' => 'So Sonarr starts as it was left', 'secret' => false],
            ],
            'beside' => [['what' => '/srv/media', 'why' => 'Your library, which the stack reads and never removes']],
            'removal' => ['state' => 'not-asked'],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Storing>
 */
function everyWayOfAskingWhatIsStored(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Storing => $why instanceof Obstacle
            ? AStackThatSaysWhatItKeeps::met($why)
            : AStackThatSaysWhatItKeeps::with(theSameThingsKept()),
        'the adapter' => static function () use ($answered): Storing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Storekeepers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheKeepingTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheKeepingSays(Storing $storing): string
{
    return $storing->storedOn(aStackThatKeepsThings(), Session::of('a-session-not-a-secret'))->either(
        kept: static function (WhatThisMachineKeeps $keeps): WhatTheKeepingTurnedOutToSay {
            $said = [];

            foreach ($keeps->roots() as $root) {
                $said[] = sprintf('root %s: %s', $root->where(), $root->what());
            }

            foreach ($keeps->kept() as $one) {
                $said[] = sprintf('kept %s at %s (%s) %s', $one->what(), $one->where(), $one->why(), $one->secret()->value);
            }

            foreach ($keeps->beside() as $one) {
                $said[] = sprintf('beside %s: %s', $one->what(), $one->why());
            }

            return new WhatTheKeepingTurnedOutToSay(implode(' | ', $said));
        },
        met: static fn(Obstacle $why): WhatTheKeepingTurnedOutToSay => new WhatTheKeepingTurnedOutToSay($why->value),
    )->said;
}

it('N6-R7 — comes away with where things are kept, what, why, and which hold a secret', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItKeeps()));

    foreach (everyWayOfAskingWhatIsStored($answered) as $which => $make) {
        expect(everythingTheKeepingSays($make()))->toBe(
            'root /srv/lemonfiber: Everything the stack writes'
            . ' | kept The VPN credentials at /srv/lemonfiber/secrets/vpn (So the tunnel can be raised) secret'
            . ' | kept Sonarr\'s settings at /srv/lemonfiber/config/sonarr (So Sonarr starts as it was left) plain'
            . ' | beside /srv/media: Your library, which the stack reads and never removes',
            $which,
        );
    }
});

it('N1-R44 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatIsStored($answered, $why) as $which => $make) {
            expect(everythingTheKeepingSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N6-R7 — a secret flag this app cannot read is an obstacle, never a thing with no secret in it', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItKeeps(secret: 'yes')))]);

    expect(everythingTheKeepingSays(new Storekeepers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StoredEnvelope', whatAStackSaysItKeeps()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
