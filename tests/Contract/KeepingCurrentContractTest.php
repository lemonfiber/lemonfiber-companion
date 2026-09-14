<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Underway;
use Modules\Kernel\Api\Upkeep;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Upkeepers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsCurrent;

// The KeepingCurrent contract, run against the adapter and against the fake.
//
// `G2`'s shape. What is asserted here is what both must agree on: which of the
// three states the stack is in, which releases are worth offering, and that a
// refusal is told apart from a stack that did not answer. What only the adapter
// can be asked — that an unreadable payload becomes an obstacle rather than an
// exception — is asserted of it in its own suite, because the fake has no
// payload to be short of.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose upkeep is asked after. */
function aStackWithUpdates(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionTheStackIsAskedAboutItsUpkeepWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What both implementations answer with, where they answer.
 *
 * One release the household would notice, one it would not, and one that has
 * been taken back — so that the filtering `N2-R16` asks for has something to do.
 */
function theSameStanding(): Upkeep
{
    return Upkeep::runningOn(
        HowCurrent::Pending,
        Release::called('4.0.15', noticeable: false, withdrawn: false),
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false),
            Release::called('4.0.16', noticeable: false, withdrawn: false),
            Release::called('4.0.17', noticeable: true, withdrawn: true),
        ),
        // One service, not two. The stack has already refused the other, and a
        // confirmation naming it would have somebody agree to a service that
        // was never going to move.
        Services::these(ServiceId::called('jellyfin')),
    );
}

/** What the far end answers where that is where it stands. */
function anUpkeepAnswer(): MockResponse
{
    return MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'update',
            'data' => [
                'state' => 'pending',
                'running' => ['version' => '4.0.15', 'user_facing' => false],
                'changes' => [
                    ['service' => 'jellyfin', 'refused' => false],
                    // Refused, so it is not a service an update would change and
                    // naming it in a confirmation would have somebody agree to
                    // one that was never going to move.
                    ['service' => 'sonarr', 'refused' => true],
                ],
                'changelog' => [
                    'releases' => [
                        ['version' => '4.1.0', 'user_facing' => true],
                        ['version' => '4.0.16', 'user_facing' => false],
                        ['version' => '4.0.17', 'user_facing' => true, 'withdrawn' => '2026-09-01'],
                    ],
                ],
            ],
        ]),
    );
}

/**
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): KeepingCurrent>
 */
function everyWayOfKeepingCurrent(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): KeepingCurrent => $why instanceof Obstacle
            ? AStackThatKeepsCurrent::met($why)
            : AStackThatKeepsCurrent::with(theSameStanding()),
        'the adapter' => static function () use ($answered): KeepingCurrent {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Upkeepers(new PinnedClients());
        },
    ];
}

/** The services an update would change, whichever implementation answered. */
function whatItWouldChange(KeepingCurrent $keeping): Services
{
    return $keeping->standing(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith())->either(
        stands: static fn(Upkeep $upkeep): Services => $upkeep->changing(),
        // Nothing would change, because nothing was read. Which obstacle it
        // was is what the rule above asserts; here it only has to not be a list.
        met: static fn(): Services => Services::none(),
    );
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheUpkeepTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Where the stack stands, folded to a word, so two answers can be compared. */
function whereItStands(KeepingCurrent $keeping): string
{
    return $keeping->standing(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith())->either(
        stands: static function (Upkeep $upkeep): WhatTheUpkeepTurnedOutToSay {
            $offered = [];

            foreach ($upkeep->waiting() as $release) {
                $offered[] = $release->version();
            }

            return new WhatTheUpkeepTurnedOutToSay(sprintf(
                '%s/%s/%s',
                $upkeep->how()->value,
                $upkeep->running(
                    on: static fn(Release $release): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($release->version()),
                    unstated: static fn(): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay('unstated'),
                )->said,
                implode(',', $offered),
            ));
        },
        met: static fn(Obstacle $why): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($why->name),
    )->said;
}

it('N2-R15 — comes away with the state the stack reported and the release it is on', function (): void {
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        expect(whereItStands($build()))->toStartWith('pending/4.0.15', $which);
    }
});

it('N2-R16 — leaves out the release that was taken back', function (): void {
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        expect(whereItStands($build()))
            ->toEndWith('4.1.0,4.0.16', $which)
            ->and(whereItStands($build()))->not->toContain('4.0.17', $which);
    }
});

it('N2-R17 — names only the services an update would actually change', function (): void {
    // The rule the fake could not be wrong about on its own: what makes this
    // worth asserting across both is that the adapter has to read `refused` off
    // the wire and leave that row out, and the fake has to be built the same
    // way. A confirmation that named a refused service would have somebody
    // agree to an evening that was never going to happen.
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        $named = [];

        foreach (whatItWouldChange($build()) as $service) {
            $named[] = $service->named();
        }

        expect($named)->toBe(['jellyfin'], $which);
    }
});

it('N1-R10 — tells a credential that was refused from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfKeepingCurrent($answered, $why) as $which => $build) {
            expect(whereItStands($build()))->toBe($why->name, $which);
        }
    }
});

it('N2-R17 — takes an update agreed against the services it named', function (): void {
    $taken = MockResponse::make(
        (string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'a-job']]),
    );

    $agreed = TakingAnUpdate::agreed(
        Release::called('4.1.0', noticeable: true, withdrawn: false),
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
    );

    foreach (everyWayOfKeepingCurrent($taken) as $which => $build) {
        $underway = $build()->take(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith(), $agreed);

        expect($underway)->toBeInstanceOf(Underway::class, $which);
    }
});
