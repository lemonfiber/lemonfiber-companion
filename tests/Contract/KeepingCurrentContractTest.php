<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheUpdateIsGoing;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Job;
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
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Standings;
use Modules\Sdk\Api\Upkeepers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Tree;
use Tests\Support\WhatTheContractAccepts;

// The KeepingCurrent contract, run against the adapter and against the fake.
//
// `G2`'s shape. What is asserted here is what both must agree on: where the
// services stand against their pins, what the release history holds, whether
// there is an update to offer, and that a refusal is told apart from a stack
// that did not answer. What only the adapter
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
 * The report of an update that went part way, running `4.1.0`, whose record
 * holds that release and two before it — one of them taken back. The history
 * is carried whole, withdrawn release included, because it is history rather
 * than a list of offers.
 */
function theSameStanding(): Upkeep
{
    return Upkeep::runningOn(
        AgainstThePins::Partial,
        Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
            Release::called('4.0.17', noticeable: true, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()),
            Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        ),
        // One service, not two. The stack has already refused the other, and a
        // confirmation naming it would have somebody agree to a service that
        // was never going to move.
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        theSameApplying(),
    );
}

/**
 * What became of the last update, which both implementations report.
 *
 * Two endings and not one, and the second is not *failed*: a service that took
 * the image and would not come back up sends somebody to its logs, and one that
 * never answered sends them to the machine. Flattened, both send them nowhere.
 */
function theSameApplying(): HowServicesTookIt
{
    return HowServicesTookIt::these(
        HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
        HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
    );
}

/**
 * The payload the far end answers with, as a stack would actually send it.
 *
 * Every field the contract requires, at the path the contract puts it. The rule
 * below holds it to that rather than trusting this to have been written
 * carefully, because a fixture is written by whoever wrote the reader: when the
 * two agree about a field that is not there, both are wrong the same way and
 * every assertion passes.
 *
 * `state` appears twice on this payload and means two things. The top-level one
 * says whether any service would move onto its pin; the changelog's says
 * whether the release record matches the running build. They are both words,
 * so nothing but the contract tells them apart.
 *
 * @return array<string, mixed>
 */
function whatAStackWithUpdatesSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'update',
        'data' => [
            'state' => 'partial',
            'confirmed' => true,
            'in_flight' => [],
            'stack_edits' => [],
            'applied' => [
                [
                    'service' => 'jellyfin',
                    'ending' => 'updated',
                    'from' => '4.0.14',
                    'to' => '4.0.15',
                    'reversal' => 'rollback',
                ],
                [
                    'service' => 'sonarr',
                    'ending' => 'not-started',
                    'from' => '4.0.14',
                    'to' => '4.0.15',
                    'reversal' => 'restore',
                ],
            ],
            'changes' => [
                aChangeTo('jellyfin', refused: false),
                // Refused, so it is not a service an update would change and
                // naming it in a confirmation would have somebody agree to one
                // that was never going to move.
                aChangeTo('sonarr', refused: true),
            ],
            'changelog' => [
                'state' => 'current',
                'requirements' => [],
                'running' => [
                    'version' => '4.1.0',
                    'user_facing' => true,
                    'delivers' => 'Adds series search.',
                    'tag' => 'v4.1.0',
                    'groups' => [],
                ],
                'releases' => [
                    ['version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.'],
                    ['version' => '4.0.17', 'user_facing' => true, 'withdrawn' => '2026-09-01'],
                    ['version' => '4.0.16', 'user_facing' => false],
                ],
            ],
        ],
    ];
}

/**
 * What a stack sends where its services are behind their pins.
 *
 * Nothing applied yet, two changes of which the stack refused one, and a
 * changelog whose notes match the running build — `current` there, which is
 * not what says an update is available.
 *
 * @return array<string, mixed>
 */
function whatAStackWithAnUpdateAvailableSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'update',
        'data' => [
            'state' => 'updates-available',
            'confirmed' => false,
            'in_flight' => [],
            'stack_edits' => [],
            'applied' => [],
            'changes' => [aChangeTo('jellyfin', refused: false), aChangeTo('sonarr', refused: true)],
            'changelog' => [
                'state' => 'current',
                'requirements' => [],
                'running' => [
                    'version' => '4.1.0',
                    'user_facing' => true,
                    'delivers' => 'Adds series search.',
                    'tag' => 'v4.1.0',
                    'groups' => [],
                ],
                'releases' => [
                    ['version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.'],
                    ['version' => '4.0.16', 'user_facing' => false],
                ],
            ],
        ],
    ];
}

/** The reading both implementations answer that payload with. */
function theSameStandingWithAnUpdateAvailable(): Upkeep
{
    return Upkeep::runningOn(
        AgainstThePins::UpdatesAvailable,
        Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
        Releases::these(
            Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
            Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        ),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
    );
}

/**
 * What a stack sends just after it was updated, before its notes are written.
 *
 * Every service is on its pin, so the top-level `state` is `current`. The
 * running build's release has no notes yet, so the changelog says `pending`
 * and names no running release, and its history holds only the releases
 * before this build. Nothing here is an update: the changelog's `pending` is
 * about the notes, and the releases are past ones.
 *
 * @return array<string, mixed>
 */
function whatAStackWithPendingNotesSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'update',
        'data' => [
            'state' => 'current',
            'confirmed' => false,
            'in_flight' => [],
            'stack_edits' => [],
            'applied' => [],
            'changes' => [],
            'changelog' => [
                'state' => 'pending',
                'requirements' => [],
                'releases' => [
                    ['version' => '4.0.16', 'user_facing' => true, 'delivers' => 'Adds series search.'],
                    ['version' => '4.0.15', 'user_facing' => false],
                ],
            ],
        ],
    ];
}

/** The reading both implementations answer that payload with. */
function theSameStandingWithPendingNotes(): Upkeep
{
    return Upkeep::reported(
        AgainstThePins::Current,
        Releases::these(
            Release::called('4.0.16', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
            Release::called('4.0.15', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        ),
        Services::none(),
        Services::none(),
        HowServicesTookIt::none(),
    );
}

/**
 * One row of what an update would change.
 *
 * @return array<string, mixed>
 */
function aChangeTo(string $service, bool $refused): array
{
    return [
        'service' => $service,
        'refused' => $refused,
        'because' => 'a newer image is published',
        'current' => '4.0.15',
        'target' => '4.1.0',
        'irreversible' => false,
        'jump' => 'minor',
    ];
}

/** What the far end answers where that is where it stands. */
function anUpkeepAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackWithUpdatesSends()));
}

/**
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): KeepingCurrent>
 */
function everyWayOfKeepingCurrent(MockResponse $answered, ?Obstacle $why = null, ?Upkeep $standing = null): array
{
    return [
        'the fake' => static fn(): KeepingCurrent => $why instanceof Obstacle
            ? AStackThatKeepsCurrent::met($why)
            : AStackThatKeepsCurrent::with($standing ?? theSameStanding()),
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
            $history = [];

            foreach ($upkeep->history() as $release) {
                $history[] = $release->wasWithdrawn() ? sprintf('%s(withdrawn)', $release->version()) : $release->version();
            }

            return new WhatTheUpkeepTurnedOutToSay(sprintf(
                '%s/%s/%s/%s',
                $upkeep->againstThePins()->value,
                $upkeep->inUse(
                    named: static fn(Release $inUse): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($inUse->version()),
                    unstated: static fn(): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay('unstated'),
                )->said,
                $upkeep->hasSomethingToOffer() ? 'offered' : 'nothing offered',
                implode(',', $history),
            ));
        },
        met: static fn(Obstacle $why): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($why->name),
    )->said;
}

/**
 * What became of a take, folded to a word, so two answers can be compared.
 *
 * The same shape as {@see whereItStands()} and for the same reason: `Underway`
 * hands nothing out without being asked what happens in each case, and a word
 * is what two implementations can be held to the same way. It takes the
 * agreement because a take has one, where a reading has only the stack.
 */
function howItWasTaken(KeepingCurrent $keeping, TakingAnUpdate $agreed): string
{
    return $keeping->take(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith(), $agreed)->either(
        started: static fn(Job $job): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay(
            sprintf('following %s', $job->shown()),
        ),
        met: static fn(Obstacle $why): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($why->name),
    )->said;
}

/** The yes an operator gave to the update the reading offered. */
function theUpdateTheOperatorAgreedTo(): TakingAnUpdate
{
    return TakingAnUpdate::offeredBy(theSameStandingWithAnUpdateAvailable());
}

it('N2-R15 — comes away with the state the stack reported and the release it is on', function (): void {
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        expect(whereItStands($build()))->toStartWith('partial/4.1.0/nothing offered', $which);
    }
});

it('comes away with every release the record holds, as history', function (): void {
    // The withdrawn one included and marked: history is what happened, and
    // nothing in it is offered.
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        expect(whereItStands($build()))->toEndWith('/4.1.0,4.0.17(withdrawn),4.0.16', $which);
    }
});

it('offers the update where the stack said one is available', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackWithAnUpdateAvailableSends()));

    foreach (everyWayOfKeepingCurrent($answered, standing: theSameStandingWithAnUpdateAvailable()) as $which => $build) {
        expect(whereItStands($build()))->toBe('updates-available/4.1.0/offered/4.1.0,4.0.16', $which);
    }
});

it('offers nothing where the notes are pending and every service is on its pin', function (): void {
    // The payload that used to read as *an update is waiting*: the changelog
    // says `pending` and lists releases. Both are about the release record —
    // notes not yet written for this build, and the releases before it — and
    // the top-level `current` is the stack saying no service would move.
    $answered = MockResponse::make((string) json_encode(whatAStackWithPendingNotesSends()));

    foreach (everyWayOfKeepingCurrent($answered, standing: theSameStandingWithPendingNotes()) as $which => $build) {
        expect(whereItStands($build()))->toBe('current/unstated/nothing offered/4.0.16,4.0.15', $which);
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

it('comes away from a take with the job the stack named', function (): void {
    // What the verb half promises, which its return type does not: the stack
    // took the work on and said what to ask after it by. Both sides answer the
    // same handle on purpose — the fake names one and the adapter's stack is
    // made to answer it — because a contract comparing two implementations can
    // only assert a value they can both be made to say.
    $taken = MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['job' => AStackThatKeepsCurrent::THE_JOB],
    ]));

    foreach (everyWayOfKeepingCurrent($taken) as $which => $build) {
        expect(howItWasTaken($build(), theUpdateTheOperatorAgreedTo()))
            ->toBe(sprintf('following %s', AStackThatKeepsCurrent::THE_JOB), $which);
    }
});

it('comes away from a take that did not happen with the obstacle rather than a job', function (): void {
    // The other arm, held to the same table the reading half is held to above.
    // `Underway` carries a refusal the way `WhatIsCurrent` does, and a fake
    // answering a job where the stack refused would let a screen offer to
    // follow work that was never started — which is the one thing an operator
    // cannot tell by looking.
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfKeepingCurrent($answered, $why) as $which => $build) {
            expect(howItWasTaken($build(), theUpdateTheOperatorAgreedTo()))->toBe($why->name, $which);
        }
    }
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // The rule that makes every rule above mean anything. What it refuses is a
    // fixture agreeing with the reader about a field neither the stack nor the
    // contract has — which is not a hypothetical failure: this payload once put
    // `state` and `running` at the top, `Standings` read them there, and three
    // rules passed while the screen would have refused every stack with an
    // update waiting.
    //
    // Both directions, because each catches a different mistake. A field the
    // contract does not have at that path is a reader looking in the wrong
    // place. A field it requires and this leaves out is a fixture standing in
    // for a payload no stack sends, which is a reader nothing has tested.
    foreach ([whatAStackWithUpdatesSends(), whatAStackWithAnUpdateAvailableSends(), whatAStackWithPendingNotesSends()] as $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('UpdateEnvelope', $payload))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});

it('N2-R19 — the contract still names a way back on every service', function (): void {
    // `HowAServiceTookIt` has no case for *no way back*, and this is why that
    // is safe rather than an omission. Undoing may not be offered where the
    // stack named no way back, and the stack names one every time: `reversal`
    // is required and says `rollback` or `restore`.
    //
    // The day it becomes optional, the requirement stops being answered by the
    // contract and starts needing an absent case again — so this fails and says
    // so, rather than the app quietly promising a way back it was handed null
    // for. The check a planted violation would otherwise have to prove, kept
    // where the assumption is made.
    $shape = thePayloadShapeOfTheUpdateEnvelope();

    expect($shape)
        ->toContain("reversal: 'rollback'|'restore'")
        ->and($shape)->not->toContain('reversal?:');
});

/**
 * Both ways of asking after an update taken, each set up to say the same.
 *
 * @return array<string, Closure(): KeepingCurrent>
 */
function everyWayOfFollowingAnUpdate(MockResponse $answered, HowTheUpdateIsGoing $became): array
{
    return [
        'the fake' => static fn(): KeepingCurrent => AStackThatKeepsCurrent::whichTook(theSameStanding(), $became),
        'the adapter' => static function () use ($answered): KeepingCurrent {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Upkeepers(new PinnedClients());
        },
    ];
}

/** What asking after the update produced, as a word, or the report's rows for a finished one. */
function whatTheTakingSaid(KeepingCurrent $keeping): string
{
    return $keeping
        ->whatBecameOf(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith(), Job::named(AStackThatKeepsCurrent::THE_JOB))
        ->either(
            stillRunning: static fn(): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay('still running'),
            done: static function (Upkeep $report): WhatTheUpkeepTurnedOutToSay {
                $said = [];

                foreach ($report->howItWent() as $service) {
                    $said[] = sprintf('%s:%s:%s', $service->service()->named(), $service->ending()->value, $service->undo()->value);
                }

                return new WhatTheUpkeepTurnedOutToSay(implode(' ', $said));
            },
            ended: static fn(): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay('ended'),
            met: static fn(Obstacle $why): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($why->name),
        )->said;
}

/** The report a finished update answers its handle with. */
function aFinishedUpdate(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackWithUpdatesSends()));
}

it('N2-R18 — a finished update reports each service apart from the others', function (): void {
    // The rule the fake could not be wrong about alone: the adapter has to read
    // four endings off the wire and keep them four. A `not-started` flattened
    // into a failure sends an operator to the machine when the answer is in the
    // service's own log.
    foreach (everyWayOfFollowingAnUpdate(aFinishedUpdate(), HowTheUpdateIsGoing::done(theSameStanding())) as $which => $build) {
        expect(whatTheTakingSaid($build()))->toBe('jellyfin:updated:rollback sonarr:not-started:restore', $which);
    }
});

it('N2-R18 — leads with the services that are not where the operator wanted them', function (): void {
    foreach (everyWayOfFollowingAnUpdate(aFinishedUpdate(), HowTheUpdateIsGoing::done(theSameStanding())) as $which => $build) {
        $went = $build()
            ->whatBecameOf(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith(), Job::named(AStackThatKeepsCurrent::THE_JOB))
            ->either(
                stillRunning: static fn(): HowServicesTookIt => HowServicesTookIt::none(),
                done: static fn(Upkeep $report): HowServicesTookIt => $report->howItWent(),
                ended: static fn(): HowServicesTookIt => HowServicesTookIt::none(),
                met: static fn(): HowServicesTookIt => HowServicesTookIt::none(),
            );
        $wrong = [];

        foreach ($went->thatDidNotArrive() as $service) {
            $wrong[] = $service->service()->named();
        }

        expect($wrong)->toBe(['sonarr'], $which)
            ->and($went->count())->toBe(2, $which)
            // `not-started` is a service that would not come back up, which the
            // stack knows. Unanswered is the one it does not.
            ->and($went->anythingUnanswered())->toBeFalse($which);
    }
});

it('N2-R18 — an update still running is its own answer', function (): void {
    $running = MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'update', 'job' => AStackThatKeepsCurrent::THE_JOB],
    ]), 202);

    foreach (everyWayOfFollowingAnUpdate($running, HowTheUpdateIsGoing::stillRunning()) as $which => $build) {
        expect(whatTheTakingSaid($build()))->toBe('still running', $which);
    }
});

it('an update the stack no longer has a job for is ended, not unreachable and not running', function (): void {
    // Folded into running, the screen asks after a handle nothing will ever
    // answer for; folded into unreachable, an operator is sent to look at a
    // machine that said clearly that it has no outcome to give.
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    foreach (everyWayOfFollowingAnUpdate($forgotten, HowTheUpdateIsGoing::ended()) as $which => $build) {
        expect(whatTheTakingSaid($build()))->toBe('ended', $which);
    }
});

it('N1-R10 — asking after an update tells a refused session from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfFollowingAnUpdate($answered, HowTheUpdateIsGoing::met($why)) as $which => $build) {
            expect(whatTheTakingSaid($build()))->toBe($why->name, $which);
        }
    }
});

it('asking after an update names the handle the take answered', function (): void {
    $keeping = AStackThatKeepsCurrent::whichTook(theSameStanding(), HowTheUpdateIsGoing::stillRunning());
    whatTheTakingSaid($keeping);

    expect($keeping->followed())->toHaveCount(1)
        ->and($keeping->followed()[0]->shown())->toBe(AStackThatKeepsCurrent::THE_JOB);
});

/** The `update` envelope's declared payload shape, as text. */
function thePayloadShapeOfTheUpdateEnvelope(): string
{
    $said = (string) file_get_contents(
        Tree::at('vendor/lemonfiber/sdk-php/src/Generated/UpdateEnvelope.php'),
    );

    preg_match('/@phpstan-type Data (.*)/', $said, $shape);

    return $shape[1] ?? '';
}

/** One arm's name, so a fold can be asserted on rather than counted. */
final readonly class WhichArmARowReached
{
    public function __construct(public string $said) {}
}

/**
 * A stack whose record holds one release, carrying whatever a case puts in `delivers`.
 *
 * Written out rather than reached into the payload above, because a case that
 * edited a shared fixture three levels down would be a helper about arrays and
 * not about this field. What keeps it honest is that it is held to the contract
 * below, the same way the larger payload is — a shape no stack sends would fail
 * there rather than quietly proving the reader against nothing.
 *
 * @param  array<string, mixed>  $release  the one release in the record, whole
 * @return array<string, mixed>
 */
function aStackWhoseRecordHolds(array $release): array
{
    return [
        'state' => 'updates-available',
        'confirmed' => false,
        'in_flight' => [],
        'stack_edits' => [],
        'applied' => [],
        'changes' => [],
        'changelog' => [
            'state' => 'pending',
            'requirements' => [],
            'running' => [
                'version' => '4.0.15',
                'user_facing' => false,
                'tag' => 'v4.0.15',
                'groups' => [],
            ],
            'releases' => [$release],
        ],
    ];
}

/**
 * What that release says it delivers, read through the reader the screen uses.
 *
 * @param array<string, mixed> $release the one release in the record, whole
 */
function whatThatListedReleaseDelivers(array $release): string
{
    foreach (Standings::in(new Envelope(1, 'update', aStackWhoseRecordHolds($release)))->history() as $found) {
        return $found->delivers()->either(
            said: static fn(string $prose): WhichArmARowReached => new WhichArmARowReached(
                sprintf('said:%s', $prose),
            ),
            saidNothing: static fn(): WhichArmARowReached => new WhichArmARowReached('silent'),
        )->said;
    }

    return 'nothing was read';
}

it('stands in for a release with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('UpdateEnvelope', [
        'api_version' => 1,
        'kind' => 'update',
        'data' => aStackWhoseRecordHolds([
            'version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.',
        ]),
    ]))->toBe([], "The payload these cases stand in for a stack with is not one a stack would send.\n");
});

it('E5-R6 — reads what a release delivers, so the row that draws it can say it', function (): void {
    expect(whatThatListedReleaseDelivers([
        'version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.',
    ]))->toBe('said:Adds series search.');
});

it('E5-R10 — a release the stack said nothing about is silent rather than blank', function (): void {
    // Absent, and present but empty. Both are the stack having nothing to say
    // and neither is a payload gone wrong — the contract marks the field
    // optional, and a reading that refused here would refuse the ordinary case.
    expect(whatThatListedReleaseDelivers(['version' => '4.1.0', 'user_facing' => true]))
        ->toBe('silent')
        ->and(whatThatListedReleaseDelivers([
            'version' => '4.1.0', 'user_facing' => true, 'delivers' => '',
        ]))->toBe('silent');
});

it('reads a delivers that is not text as nothing said rather than refusing the listing', function (): void {
    // The one place this reader defaults rather than refuses, and the reason is
    // the opposite of `user_facing`'s. That field is on every release and a
    // default would invent the reassuring answer; this one is prose, and a
    // number where a sentence belongs is a stack that has given none. Refusing
    // would lose the whole listing — every other release included — over a line
    // that is not what the operator is deciding on.
    expect(whatThatListedReleaseDelivers([
        'version' => '4.1.0', 'user_facing' => true, 'delivers' => 7,
    ]))->toBe('silent');
});
