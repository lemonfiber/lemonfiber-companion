<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Http\ActionRequest;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneKindUpgraded;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\UpgradingTheLibrary;
use Modules\Kernel\Api\WhatBecameOfAskingIt;
use Modules\Kernel\Api\WhatTheUpgradeCameTo;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Upgraders;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatUpgrades;
use Tests\Support\WhatTheContractAccepts;

// The UpgradingTheLibrary contract, run against the adapter and against the fake.
//
// `G2`'s shape. What both must keep is that describing fetches nothing and
// upgrading is a second call, and that the answer is kind by kind: each kind
// at its own preset and its own cost an hour, with what asking its service
// came to once the operator said yes.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine whose library would be upgraded. */
function theMachineWhoseLibraryIsUpgraded(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('u', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

function theSessionAnUpgradeIsAskedOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What a stack sends about an upgrade.
 *
 * @param  list<mixed>|null  $media
 * @return array<string, mixed>
 */
function whatAStackSaysOfAnUpgrade(bool $confirmed = false, ?array $media = null): array
{
    return [
        'api_version' => 1,
        'kind' => 'upgrade',
        'data' => [
            'confirmed' => $confirmed,
            'media' => $media ?? [
                ['media_type' => 'movies', 'preset' => 'Maximum', 'size_per_hour' => '~15 GB', 'outcome' => null],
                ['media_type' => 'tv', 'preset' => 'Space-saving', 'size_per_hour' => '~1 GB'],
            ],
        ],
    ];
}

/**
 * One kind an upgrade covers, as the wire carries it, with what asking its service came to.
 *
 * @param  array<string, string>|string|null  $outcome
 * @return array<string, mixed>
 */
function aKindUpgradedWith(array|string|null $outcome): array
{
    return ['media_type' => 'movies', 'preset' => 'Maximum', 'size_per_hour' => '~15 GB', 'outcome' => $outcome];
}

/** One answer carried out of an arm. */
final readonly class WhatTheUpgradeCameBackAs
{
    public function __construct(public string $said) {}
}

/** What an upgrade came to, as one line. */
function howAnUpgradeReadsAsText(WhatTheUpgradeCameTo $came): string
{
    return $came->either(
        said: static function (TheUpgrade $upgrade): WhatTheUpgradeCameBackAs {
            $kinds = [];

            foreach ($upgrade as $kind) {
                $kinds[] = implode('/', [$kind->kind(), $kind->preset(), $kind->sizePerHour(), $kind->asking()->saidOnTheScreen(), $kind->asking()->detail()]);
            }

            return new WhatTheUpgradeCameBackAs(sprintf('%s:%s', $upgrade->wasCarriedOut() ? 'carried out' : 'described', implode(';', $kinds)));
        },
        met: static fn(Obstacle $why): WhatTheUpgradeCameBackAs => new WhatTheUpgradeCameBackAs(sprintf('refused:%s', $why->value)),
    )->said;
}

/** The adapter, answering every request with the body given. */
function upgradersAnswering(mixed $body, int $status = 200): Upgraders
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make($body, $status)]);

    return new Upgraders(new PinnedClients());
}

/** Describing through the adapter, answered with the body given. */
function anUpgradeDescribedWith(mixed $body): string
{
    return howAnUpgradeReadsAsText(upgradersAnswering($body)->whatItWouldComeTo(theMachineWhoseLibraryIsUpgraded(), theSessionAnUpgradeIsAskedOn()));
}

/** A description the operator could agree to. */
function anUpgradeToAgreeTo(): AnUpgradeDescribed
{
    return AnUpgradeDescribed::by(TheUpgrade::described());
}

/**
 * The body the adapter last sent, with the action it was sent to.
 *
 * @return array{string, mixed}
 */
function whatTheUpgradersSent(): array
{
    $sent = MockClient::getGlobal()?->getLastRequest();

    return $sent instanceof ActionRequest ? [$sent->resolveEndpoint(), $sent->body()->all()] : ['', null];
}

/**
 * Both ways of describing an upgrade, each set up to produce the same answer.
 *
 * @return array<string, Closure(): UpgradingTheLibrary>
 */
function everyWayOfDescribingAnUpgrade(): array
{
    $described = TheUpgrade::described(
        OneKindUpgraded::reported('movies', 'Maximum', '~15 GB', WhatBecameOfAskingIt::notAsked()),
        OneKindUpgraded::reported('tv', 'Space-saving', '~1 GB', WhatBecameOfAskingIt::notAsked()),
    );

    return [
        'the fake' => static fn(): UpgradingTheLibrary => AStackThatUpgrades::describing($described, TheUpgrade::carriedOut()),
        'the adapter' => static fn(): UpgradingTheLibrary => upgradersAnswering(whatAStackSaysOfAnUpgrade()),
    ];
}

foreach (everyWayOfDescribingAnUpgrade() as $name => $build) {
    it(sprintf('%s describes an upgrade kind by kind, each at its own preset and cost, asking nothing', $name), function () use ($build): void {
        expect(howAnUpgradeReadsAsText($build()->whatItWouldComeTo(theMachineWhoseLibraryIsUpgraded(), theSessionAnUpgradeIsAskedOn())))
            ->toBe('described:movies/Maximum/~15 GB/quality.asked.not-asked/;tv/Space-saving/~1 GB/quality.asked.not-asked/');
    });
}

it('the fake and the adapter both refuse rather than answer with nothing', function (): void {
    expect(anUpgradeDescribedWith(['nothing' => 'the contract knows']))->toBe('refused:no_answer')
        ->and(howAnUpgradeReadsAsText(AStackThatUpgrades::met(Obstacle::StackDidNotAnswer)->upgrade(
            theMachineWhoseLibraryIsUpgraded(),
            theSessionAnUpgradeIsAskedOn(),
            anUpgradeToAgreeTo(),
        )))->toBe('refused:no_answer');
});

it('the fake keeps describing apart from upgrading', function (): void {
    $fake = AStackThatUpgrades::describing(TheUpgrade::described(), TheUpgrade::carriedOut());

    $described = howAnUpgradeReadsAsText($fake->whatItWouldComeTo(theMachineWhoseLibraryIsUpgraded(), theSessionAnUpgradeIsAskedOn()));

    expect([$described, $fake->descriptions(), $fake->upgrades()])->toBe(['described:', 1, 0]);

    $done = howAnUpgradeReadsAsText($fake->upgrade(theMachineWhoseLibraryIsUpgraded(), theSessionAnUpgradeIsAskedOn(), anUpgradeToAgreeTo()));

    expect([$done, $fake->descriptions(), $fake->upgrades()])->toBe(['carried out:', 1, 1]);
});

it('asks what upgrading would come to without a yes', function (): void {
    anUpgradeDescribedWith(whatAStackSaysOfAnUpgrade());

    expect(whatTheUpgradersSent())->toBe(['/api/actions/quality-upgrade', ['confirm' => false]]);
});

it('upgrades with the yes, and reads what each service was asked', function (): void {
    $came = upgradersAnswering(whatAStackSaysOfAnUpgrade(confirmed: true, media: [
        aKindUpgradedWith(['state' => 'started']),
        [...aKindUpgradedWith(['state' => 'not-started']), 'media_type' => 'tv'],
        [...aKindUpgradedWith(['state' => 'failed', 'detail' => 'Sonarr said no']), 'media_type' => 'anime'],
    ]))->upgrade(theMachineWhoseLibraryIsUpgraded(), theSessionAnUpgradeIsAskedOn(), anUpgradeToAgreeTo());

    expect(whatTheUpgradersSent())->toBe(['/api/actions/quality-upgrade', ['confirm' => true]])
        ->and(howAnUpgradeReadsAsText($came))->toBe(
            'carried out:movies/Maximum/~15 GB/quality.asked.started/;'
            . 'tv/Maximum/~15 GB/quality.asked.not-started/;'
            . 'anime/Maximum/~15 GB/quality.asked.failed/Sonarr said no',
        );
});

it('refuses an upgrade answer it cannot read rather than drawing part of one', function (mixed $body): void {
    expect(anUpgradeDescribedWith($body))->toBe('refused:no_answer');
})->with([
    'data that is not a table' => [['api_version' => 1, 'kind' => 'upgrade', 'data' => 'movies']],
    'confirmed that is not yes or no' => [[...whatAStackSaysOfAnUpgrade(), 'data' => ['confirmed' => 'no', 'media' => []]]],
    'no confirmed at all' => [[...whatAStackSaysOfAnUpgrade(), 'data' => ['media' => []]]],
    'media that are not a list' => [[...whatAStackSaysOfAnUpgrade(), 'data' => ['confirmed' => false, 'media' => 'movies']]],
    'a kind that is not a table' => [whatAStackSaysOfAnUpgrade(media: [['movies']])],
    'a kind with a blank media type' => [whatAStackSaysOfAnUpgrade(media: [[...aKindUpgradedWith(null), 'media_type' => ' ']])],
    'a kind with no cost' => [whatAStackSaysOfAnUpgrade(media: [array_diff_key(aKindUpgradedWith(null), ['size_per_hour' => true])])],
    'an outcome that is not a table' => [whatAStackSaysOfAnUpgrade(media: [aKindUpgradedWith('started')])],
    'an outcome the contract has not got' => [whatAStackSaysOfAnUpgrade(media: [aKindUpgradedWith(['state' => 'mostly'])])],
    'a failure with no reason' => [whatAStackSaysOfAnUpgrade(media: [aKindUpgradedWith(['state' => 'failed', 'detail' => ''])])],
    'an answer of another kind' => [['api_version' => 1, 'kind' => 'quality', 'data' => []]],
]);

it('says the credential was refused when the stack refuses an upgrade', function (): void {
    expect(howAnUpgradeReadsAsText(upgradersAnswering(['error' => 'no'], 401)->upgrade(
        theMachineWhoseLibraryIsUpgraded(),
        theSessionAnUpgradeIsAskedOn(),
        anUpgradeToAgreeTo(),
    )))->toBe('refused:credential_refused');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UpgradeEnvelope', whatAStackSaysOfAnUpgrade(confirmed: true, media: [
        aKindUpgradedWith(['state' => 'failed', 'detail' => 'Sonarr said no']),
    ])))->toBe([]);
});
