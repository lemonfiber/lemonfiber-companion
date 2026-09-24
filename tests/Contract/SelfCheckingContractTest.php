<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SelfChecking;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Kernel\Api\WhatAnUpdateWouldBring;
use Modules\Kernel\Api\WhatIsReleased;
use Modules\Kernel\Api\WhereThisCopyStands;
use Modules\Sdk\Api\Inspectors;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatChecksItself;
use Tests\Support\WhatTheContractAccepts;

// The SelfChecking contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `MeasuringContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked about its running copy. */
function aStackThatKnowsItsOwnVersion(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameCopy(): ThisCopyOfLemonfiber
{
    return ThisCopyOfLemonfiber::reported(
        '0.15.0',
        HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Homebrew, 'brew'),
        WhereThisCopyStands::ManagedExternally,
        WhatIsReleased::said('0.16.0', '## New\n- Plugins'),
        '',
        HowItWouldBeUpdated::byRunning('brew upgrade lemonfiber'),
        WhatAnUpdateWouldBring::said('The program, and the stack definition it ships with', 'Your settings and library are left alone; the stack restarts'),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfItself(string $installed = 'homebrew'): array
{
    return [
        'api_version' => 1,
        'kind' => 'self-update',
        'data' => [
            'standing' => 'managed-externally',
            'running' => '0.15.0',
            'at' => '/opt/homebrew/bin/lemonfiber',
            'installed' => $installed,
            'owner' => 'brew',
            'offered' => '0.16.0',
            'changed' => '## New\n- Plugins',
            'command' => 'brew upgrade lemonfiber',
            'afterwards' => 'Your settings and library are left alone; the stack restarts',
            'carries' => 'The program, and the stack definition it ships with',
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): SelfChecking>
 */
function everyWayOfAskingAboutItself(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): SelfChecking => $why instanceof Obstacle
            ? AStackThatChecksItself::met($why)
            : AStackThatChecksItself::with(theSameCopy()),
        'the adapter' => static function () use ($answered): SelfChecking {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Inspectors(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheCopyTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheCopySays(SelfChecking $checking): string
{
    return $checking->checkedOn(aStackThatKnowsItsOwnVersion(), Session::of('a-session-not-a-secret'))->either(
        found: static fn(ThisCopyOfLemonfiber $copy): WhatTheCopyTurnedOutToSay => new WhatTheCopyTurnedOutToSay(sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s',
            $copy->running(),
            $copy->gotThere()->installed()->value,
            $copy->gotThere()->owner(),
            $copy->stands()->value,
            $copy->released()->version(),
            $copy->released()->changed(),
            $copy->untold(),
            $copy->updatedBy()->either(
                byRunning: static fn(string $command): WhatTheCopyTurnedOutToSay => new WhatTheCopyTurnedOutToSay(sprintf('run %s', $command)),
                instead: static fn(string $why): WhatTheCopyTurnedOutToSay => new WhatTheCopyTurnedOutToSay(sprintf('instead %s', $why)),
                notSaid: static fn(): WhatTheCopyTurnedOutToSay => new WhatTheCopyTurnedOutToSay('-'),
            )->said,
            $copy->brings()->carries(),
            $copy->brings()->afterwards(),
        )),
        met: static fn(Obstacle $why): WhatTheCopyTurnedOutToSay => new WhatTheCopyTurnedOutToSay($why->value),
    )->said;
}

it('comes away with how it was installed, the command that would update it, and what an update brings', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfItself()));

    foreach (everyWayOfAskingAboutItself($answered) as $which => $make) {
        expect(everythingTheCopySays($make()))->toBe(
            '0.15.0|homebrew|brew|managed-externally|0.16.0|## New\n- Plugins||run brew upgrade lemonfiber|The program, and the stack definition it ships with|Your settings and library are left alone; the stack restarts',
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
        foreach (everyWayOfAskingAboutItself($answered, $why) as $which => $make) {
            expect(everythingTheCopySays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('an installation this app cannot read is an obstacle, never one lemonfiber can replace', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItself('snap')))]);

    expect(everythingTheCopySays(new Inspectors(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the update endpoint about the running copy, and nothing else', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfItself()))]);

    everythingTheCopySays(new Inspectors(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/update')
        ->and($sent?->query()->all())->toBe(['what' => 'self']);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SelfUpdateEnvelope', whatAStackSaysOfItself()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
