<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADeviceToWatchOn;
use Modules\Kernel\Api\Advising;
use Modules\Kernel\Api\APossibleCause;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingThatGoesWrong;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheDevices;
use Modules\Kernel\Api\TheTroubles;
use Modules\Kernel\Api\WhatToWatchOn;
use Modules\Kernel\Api\WhyPlaybackMayStruggle;
use Modules\Sdk\Api\Advisers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatAdvises;
use Tests\Support\WhatTheContractAccepts;

// The Advising contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `SelfCheckingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked which app to watch on. */
function aStackGivingAdvice(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameAdvice(): WhatToWatchOn
{
    return WhatToWatchOn::advised(
        TheDevices::of(
            ADeviceToWatchOn::rated('An iPhone', 'Jellyfin for iOS', HowWellADeviceIsServed::Good, '', ''),
            ADeviceToWatchOn::rated('An older smart TV', 'The TV browser', HowWellADeviceIsServed::Poor, 'Subtitles may lag', 'A streaming stick'),
        ),
        'Every one of these works on the home network only',
        'Nothing is installed on anybody\'s device for them',
        WhyPlaybackMayStruggle::said('Archival', 'This machine cannot transcode 4K in hardware', 'Choose the Balanced preset'),
        TheTroubles::of(SomethingThatGoesWrong::said('It keeps buffering', APossibleCause::said('The Wi-Fi is weak', 'Only far from the router', 'Move closer'))),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackAdvises(string $support = 'poor'): array
{
    return [
        'api_version' => 1,
        'kind' => 'clients',
        'data' => [
            'devices' => [
                ['device' => 'An iPhone', 'client' => 'Jellyfin for iOS', 'support' => 'good'],
                ['device' => 'An older smart TV', 'client' => 'The TV browser', 'support' => $support, 'caution' => 'Subtitles may lag', 'instead' => 'A streaming stick'],
            ],
            'only_at_home' => 'Every one of these works on the home network only',
            'nothing_is_installed' => 'Nothing is installed on anybody\'s device for them',
            'straining' => ['preset' => 'Archival', 'caution' => 'This machine cannot transcode 4K in hardware', 'instead' => 'Choose the Balanced preset'],
            'trouble' => [
                ['symptom' => 'It keeps buffering', 'causes' => [['because' => 'The Wi-Fi is weak', 'tell' => 'Only far from the router', 'fix' => 'Move closer']]],
            ],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Advising>
 */
function everyWayOfAskingForAdvice(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Advising => $why instanceof Obstacle
            ? AStackThatAdvises::met($why)
            : AStackThatAdvises::advising(theSameAdvice()),
        'the adapter' => static function () use ($answered): Advising {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Advisers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheAdviceTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to lines, so two answers can be compared. */
function everythingTheAdviceSays(Advising $advising): string
{
    return $advising->advisedBy(aStackGivingAdvice(), Session::of('a-session-not-a-secret'))->either(
        found: static function (WhatToWatchOn $advice): WhatTheAdviceTurnedOutToSay {
            $lines = [
                $advice->onlyAtHome(),
                $advice->nothingIsInstalled(),
                sprintf('%s|%s|%s', $advice->straining()->preset(), $advice->straining()->caution(), $advice->straining()->instead()),
            ];

            foreach ($advice->devices() as $device) {
                $lines[] = sprintf('%s|%s|%s|%s|%s', $device->device(), $device->client(), $device->support()->value, $device->caution(), $device->instead());
            }

            foreach ($advice->troubles() as $trouble) {
                foreach ($trouble as $cause) {
                    $lines[] = sprintf('%s|%s|%s|%s', $trouble->symptom(), $cause->because(), $cause->tell(), $cause->fix());
                }
            }

            return new WhatTheAdviceTurnedOutToSay(implode("\n", $lines));
        },
        met: static fn(Obstacle $why): WhatTheAdviceTurnedOutToSay => new WhatTheAdviceTurnedOutToSay($why->value),
    )->said;
}

it('comes away with every device, its rating and what to use instead, and what to do when it does not work', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackAdvises()));

    foreach (everyWayOfAskingForAdvice($answered) as $which => $make) {
        expect(everythingTheAdviceSays($make()))->toBe(
            "Every one of these works on the home network only\n"
            . "Nothing is installed on anybody's device for them\n"
            . "Archival|This machine cannot transcode 4K in hardware|Choose the Balanced preset\n"
            . "An iPhone|Jellyfin for iOS|good||\n"
            . "An older smart TV|The TV browser|poor|Subtitles may lag|A streaming stick\n"
            . 'It keeps buffering|The Wi-Fi is weak|Only far from the router|Move closer',
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
        foreach (everyWayOfAskingForAdvice($answered, $why) as $which => $make) {
            expect(everythingTheAdviceSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a rating this app cannot read is an obstacle, never the nearest rating', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackAdvises('excellent')))]);

    expect(everythingTheAdviceSays(new Advisers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the clients endpoint, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackAdvises()))]);

    everythingTheAdviceSays(new Advisers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/clients')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ClientsEnvelope', whatAStackAdvises()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
