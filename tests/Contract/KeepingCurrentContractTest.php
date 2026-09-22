<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowCurrent;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
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
use Modules\Kernel\Api\VersionInUse;
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
 * been taken back — so that filtering down to the ones worth showing has
 * something to do.
 *
 * One of them says what it delivers and one does not, for the same reason: the
 * wire marks that field optional, both are real answers, and a reading that
 * folded them into one string would draw a release the stack said nothing
 * about as a row with a blank where a sentence belongs.
 */
function theSameStanding(): Upkeep
{
    return Upkeep::runningOn(
        HowCurrent::Pending,
        VersionInUse::of(Release::called('4.0.15', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing())),
        Releases::these(
            Release::called(
                '4.1.0',
                noticeable: true,
                withdrawn: false,
                delivers: WhatAReleaseDelivers::said('Adds series search.'),
            ),
            Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
            Release::called('4.0.17', noticeable: true, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()),
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
 * says how the last applied update finished; the *current, pending, stale*
 * triple a household is shown is under `changelog`. They are both words, so
 * nothing but the contract tells them apart.
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
                'state' => 'pending',
                'requirements' => [],
                'running' => [
                    'version' => '4.0.15',
                    'user_facing' => false,
                    'tag' => 'v4.0.15',
                    'groups' => [],
                ],
                'releases' => [
                    ['version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.'],
                    ['version' => '4.0.16', 'user_facing' => false],
                    ['version' => '4.0.17', 'user_facing' => true, 'withdrawn' => '2026-09-01'],
                ],
            ],
        ],
    ];
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
                $upkeep->inUse(
                    named: static fn(VersionInUse $inUse): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay($inUse->version()),
                    unstated: static fn(): WhatTheUpkeepTurnedOutToSay => new WhatTheUpkeepTurnedOutToSay('unstated'),
                )->said,
                implode(',', $offered),
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

/** The yes an operator gave, naming the two services the stack said would change. */
function theUpdateTheOperatorAgreedTo(): TakingAnUpdate
{
    return TakingAnUpdate::agreed(
        Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
        Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
        Services::none(),
    );
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
    expect(WhatTheContractAccepts::complaintsAbout('UpdateEnvelope', whatAStackWithUpdatesSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
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

it('N2-R18 — reports each service of an applied update apart from the others', function (): void {
    // The rule the fake could not be wrong about alone: what makes it worth
    // asserting across both is that the adapter has to read four endings off
    // the wire and keep them four. A `not-started` flattened into a failure
    // sends an operator to the machine when the answer is in the service's own
    // log, and the wire carries the difference either way.
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        $said = [];

        foreach (whatTheUpdateCameTo($build()) as $service) {
            $said[] = sprintf(
                '%s:%s:%s',
                $service->service()->named(),
                $service->ending()->value,
                $service->undo()->value,
            );
        }

        expect($said)->toBe([
            'jellyfin:updated:rollback',
            'sonarr:not-started:restore',
        ], $which);
    }
});

it('N2-R18 — leads with the services that are not where the operator wanted them', function (): void {
    foreach (everyWayOfKeepingCurrent(anUpkeepAnswer()) as $which => $build) {
        $went = whatTheUpdateCameTo($build());
        $wrong = [];

        foreach ($went->thatDidNotArrive() as $service) {
            $wrong[] = $service->service()->named();
        }

        expect($wrong)->toBe(['sonarr'], $which)
            ->and($went->count())->toBe(2, $which)
            ->and($went->isEmpty())->toBeFalse($which)
            // `not-started` is a service that would not come back up, which the
            // stack knows. Unanswered is the one it does not, and a screen that
            // read them as one would offer a remedy for a situation it is
            // guessing at.
            ->and($went->anythingUnanswered())->toBeFalse($which);
    }
});

/**
 * What became of each service, whichever implementation answered.
 *
 * Named for the update rather than for the services, because the root suites
 * share one namespace (`G10`) and `NotifierContractTest` already spends the
 * shorter name on what became of a notification.
 */
function whatTheUpdateCameTo(KeepingCurrent $keeping): HowServicesTookIt
{
    return $keeping->standing(aStackWithUpdates(), theSessionTheStackIsAskedAboutItsUpkeepWith())->either(
        stands: static fn(Upkeep $upkeep): HowServicesTookIt => $upkeep->howItWent(),
        // Nothing was applied, because nothing was read. Which obstacle it was
        // is what the rule about obstacles asserts; here it only has to not be
        // a list.
        met: static fn(): HowServicesTookIt => HowServicesTookIt::none(),
    );
}

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
 * A stack with one release waiting, carrying whatever a case puts in `delivers`.
 *
 * Written out rather than reached into the payload above, because a case that
 * edited a shared fixture three levels down would be a helper about arrays and
 * not about this field. What keeps it honest is that it is held to the contract
 * below, the same way the larger payload is — a shape no stack sends would fail
 * there rather than quietly proving the reader against nothing.
 *
 * @param  array<string, mixed>  $release  the one waiting release, whole
 * @return array<string, mixed>
 */
function aStackWhoseWaitingReleaseIs(array $release): array
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
 * @param array<string, mixed> $release the one waiting release, whole
 */
function whatThatWaitingReleaseDelivers(array $release): string
{
    foreach (Standings::in(new Envelope(1, 'update', aStackWhoseWaitingReleaseIs($release)))->waiting() as $found) {
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
        'data' => aStackWhoseWaitingReleaseIs([
            'version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.',
        ]),
    ]))->toBe([], "The payload these cases stand in for a stack with is not one a stack would send.\n");
});

it('E5-R6 — reads what a waiting release delivers, so the row the control sits on can say it', function (): void {
    expect(whatThatWaitingReleaseDelivers([
        'version' => '4.1.0', 'user_facing' => true, 'delivers' => 'Adds series search.',
    ]))->toBe('said:Adds series search.');
});

it('E5-R10 — a release the stack said nothing about is silent rather than blank', function (): void {
    // Absent, and present but empty. Both are the stack having nothing to say
    // and neither is a payload gone wrong — the contract marks the field
    // optional, and a reading that refused here would refuse the ordinary case.
    expect(whatThatWaitingReleaseDelivers(['version' => '4.1.0', 'user_facing' => true]))
        ->toBe('silent')
        ->and(whatThatWaitingReleaseDelivers([
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
    expect(whatThatWaitingReleaseDelivers([
        'version' => '4.1.0', 'user_facing' => true, 'delivers' => 7,
    ]))->toBe('silent');
});
