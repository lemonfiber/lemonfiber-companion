<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AServiceBeside;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheDoorCameToBe;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\TheServicesBeside;
use Modules\Kernel\Api\Welcoming;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHouseholdBegins;
use Modules\Sdk\Api\Doorkeepers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackWithAFrontDoor;
use Tests\Support\WhatTheContractAccepts;

// The Welcoming contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `SelfCheckingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked for its front door. */
function aStackWithADoorToAskAbout(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameDoor(): TheFrontDoor
{
    return TheFrontDoor::reported(
        WhereTheFrontDoorStands::Established,
        'Send people to Jellyseerr',
        HowTheDoorCameToBe::refused('homepage', 'It lists every service'),
        WhereTheHouseholdBegins::at('Jellyseerr', WhatItFaces::Asking, AnAddressToHand::at('http://loft.local:5055', 'Only on the home network')),
        TheServicesBeside::of(
            AServiceBeside::said('Jellyfin', WhatItFaces::Watching, 'Nothing can be asked for there', AnAddressToHand::at('http://loft.local:8096', '')),
            AServiceBeside::said('Homepage', WhatItFaces::Operators, 'It shows services the house should not see', AnAddressToHand::none()),
        ),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsDoor(string $standing = 'established'): array
{
    return [
        'api_version' => 1,
        'kind' => 'front-door',
        'data' => [
            'standing' => $standing,
            'meaning' => 'Send people to Jellyseerr',
            'chosen' => ['chosen' => 'refused', 'door' => ['named' => 'homepage', 'because' => 'It lists every service']],
            'service' => 'Jellyseerr',
            'facing' => 'asking',
            'address' => ['url' => 'http://loft.local:5055', 'caution' => 'Only on the home network'],
            'beside' => [
                ['service' => 'Jellyfin', 'facing' => 'watching', 'because' => 'Nothing can be asked for there', 'address' => ['url' => 'http://loft.local:8096']],
                ['service' => 'Homepage', 'facing' => 'operators', 'because' => 'It shows services the house should not see'],
            ],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Welcoming>
 */
function everyWayOfAskingForTheDoor(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Welcoming => $why instanceof Obstacle
            ? AStackWithAFrontDoor::met($why)
            : AStackWithAFrontDoor::with(theSameDoor()),
        'the adapter' => static function () use ($answered): Welcoming {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Doorkeepers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheDoorTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to lines, so two answers can be compared. */
function everythingTheDoorSays(Welcoming $welcoming): string
{
    return $welcoming->frontDoorOf(aStackWithADoorToAskAbout(), Session::of('a-session-not-a-secret'))->either(
        found: static function (TheFrontDoor $door): WhatTheDoorTurnedOutToSay {
            $lines = [
                sprintf('%s|%s', $door->standing()->value, $door->meaning()),
                sprintf('%s|%s|%s', $door->chosen()->how()->value, $door->chosen()->named(), $door->chosen()->because()),
                $door->begins()->either(
                    at: static fn(string $service, WhatItFaces $facing, AnAddressToHand $address): WhatTheDoorTurnedOutToSay => new WhatTheDoorTurnedOutToSay(sprintf('%s|%s|%s|%s', $service, $facing->value, $address->url(), $address->caution())),
                    nowhere: static fn(): WhatTheDoorTurnedOutToSay => new WhatTheDoorTurnedOutToSay('nowhere'),
                )->said,
            ];

            foreach ($door->beside() as $service) {
                $lines[] = sprintf('%s|%s|%s|%s|%s', $service->service(), $service->facing()->value, $service->because(), $service->address()->url(), $service->address()->caution());
            }

            return new WhatTheDoorTurnedOutToSay(implode("\n", $lines));
        },
        met: static fn(Obstacle $why): WhatTheDoorTurnedOutToSay => new WhatTheDoorTurnedOutToSay($why->value),
    )->said;
}

it('comes away with the door, how it came to be, and every address as the stack sent it', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItsDoor()));

    foreach (everyWayOfAskingForTheDoor($answered) as $which => $make) {
        expect(everythingTheDoorSays($make()))->toBe(
            "established|Send people to Jellyseerr\n"
            . "refused|homepage|It lists every service\n"
            . "Jellyseerr|asking|http://loft.local:5055|Only on the home network\n"
            . "Jellyfin|watching|Nothing can be asked for there|http://loft.local:8096|\n"
            . 'Homepage|operators|It shows services the house should not see||',
            $which,
        );
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForTheDoor($answered, $why) as $which => $make) {
            expect(everythingTheDoorSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a standing this app cannot read is an obstacle, never a door drawn as open', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsDoor('ajar')))]);

    expect(everythingTheDoorSays(new Doorkeepers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the front-door endpoint, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItsDoor()))]);

    everythingTheDoorSays(new Doorkeepers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/front-door')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('FrontDoorEnvelope', whatAStackSaysOfItsDoor()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
