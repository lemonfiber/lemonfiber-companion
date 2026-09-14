<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Sdk\Api\Menders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatWouldMend;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The Mending contract, run against the adapter and against the fake.
//
// `G2`'s shape. This port is the one where a fake drifting from the adapter
// does the most damage, because the thing it stands in for is a machine
// changing an operator's disk: every screen test will use the fake, and a fake
// that could not produce the awkward states would let a screen ship with no
// answer for them.
//
// Four states are asserted rather than three, because the port has four:
// running, finished, *ended*, and unreachable. Ended is the one an
// implementation is most tempted to fold, and the only place it can be pinned
// against both halves is here.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack being asked what it would put right. */
function aStackThatMightMend(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionARepairIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The listing both implementations answer with, where they answer. */
function theSameOffer(): Offer
{
    return Offer::of('agreement-a-test-can-name', Repairs::of(
        Repair::offered(
            'storage.one-filesystem',
            'Move the library onto the larger disk',
            Effects::of('Downloads pause while it moves'),
            Undoing::Possible,
        ),
        Repair::offered(
            'credentials.expired',
            'Forget the expired credential',
            Effects::nothingElse(),
            Undoing::Permanent,
        ),
    ));
}

/**
 * The payload a stack sends when it takes an action on.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * @return array<string, mixed>
 */
function whatAStackNamingAJobSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'repair', 'job' => AStackThatWouldMend::THE_JOB],
    ];
}

/** What a stack answers when it takes an action on. */
function anAcknowledgement(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackNamingAJobSends()), 202);
}

/**
 * The payload a stack sends where the offer is finished and nothing was acted on.
 *
 * @return array<string, mixed>
 */
function whatAStackOffering(): array
{
    return [
        'api_version' => 1,
        'kind' => 'repair',
        'data' => [
            'acted' => false,
            'agreement' => 'agreement-a-test-can-name',
            // Empty rather than absent: a stack that found nothing beyond what
            // it can put right still sends the field, and a fixture leaving it
            // out would let a reader that never looks at it pass.
            'beyond' => [],
            'mended' => [],
            'offered' => [
                [
                    'check' => 'storage.one-filesystem',
                    'does' => 'Move the library onto the larger disk',
                    'effects' => ['Downloads pause while it moves'],
                    'reversible' => true,
                ],
                [
                    'check' => 'credentials.expired',
                    'does' => 'Forget the expired credential',
                    'effects' => [],
                    'reversible' => false,
                ],
            ],
        ],
    ];
}

/** What a finished offer looks like on the wire. */
function anOfferedListing(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackOffering()));
}

/** What a stack answers while the work is still going. */
function stillGoing(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackNamingAJobSends()), 202);
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheRepairTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** What starting the work produced, as a word. */
function whatStartingSaid(Mending $mending): string
{
    return $mending->wouldPutRight(aStackThatMightMend(), theSessionARepairIsAskedWith())->either(
        started: static fn(Job $job): WhatTheRepairTurnedOutToSay
            => new WhatTheRepairTurnedOutToSay(sprintf('started %s', $job->shown())),
        met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
            => new WhatTheRepairTurnedOutToSay($why->value),
    )->said;
}

/** What reading the handle produced, as a word. */
function whatTheHandleSaid(Mending $mending): string
{
    return $mending
        ->whatBecameOf(aStackThatMightMend(), theSessionARepairIsAskedWith(), Job::named(AStackThatWouldMend::THE_JOB))
        ->either(
            stillRunning: static fn(): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay('still running'),
            offering: static function (Offer $offer): WhatTheRepairTurnedOutToSay {
                $said = [];

                foreach ($offer->repairs() as $repair) {
                    $said[] = $repair->stated(
                        static fn(string $does, Effects $effects, Undoing $undoing): WhatTheRepairTurnedOutToSay
                            => new WhatTheRepairTurnedOutToSay(
                                sprintf('%s/%d/%s', $does, $effects->count(), $undoing->value),
                            ),
                    )->said;
                }

                return new WhatTheRepairTurnedOutToSay(sprintf('%s: %s', $offer->named(), implode(' | ', $said)));
            },
            ended: static fn(): WhatTheRepairTurnedOutToSay => new WhatTheRepairTurnedOutToSay('ended'),
            met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay($why->value),
        )->said;
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Mending>
 */
function everyWayOfMending(MockResponse $answered, Closure $fake): array
{
    return [
        'the fake' => $fake,
        'the adapter' => static function () use ($answered): Mending {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Menders(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

it('N2-R7 — asking what would be put right answers a handle, not a listing', function (): void {
    // The whole reason this port has two methods. A stack does not answer
    // *here is what I would do*; it answers *I have started, ask me about this
    // name* — and a port hiding that would have to wait inside itself.
    $ways = everyWayOfMending(
        anAcknowledgement(),
        static fn(): Mending => AStackThatWouldMend::offering(theSameOffer()),
    );

    foreach ($ways as $which => $make) {
        expect(whatStartingSaid($make()))
            ->toBe(sprintf('started %s', AStackThatWouldMend::THE_JOB), $which);
    }
});

it('N2-R4 — a finished offer states what each repair does, affects and can undo', function (): void {
    // All three clauses, because they are one requirement. A listing carrying
    // the sentence and dropping whether it can be undone would satisfy any
    // assertion about what appeared and still leave somebody agreeing to
    // something permanent believing they could take it back.
    $ways = everyWayOfMending(
        anOfferedListing(),
        static fn(): Mending => AStackThatWouldMend::offering(theSameOffer()),
    );

    foreach ($ways as $which => $make) {
        expect(whatTheHandleSaid($make()))->toBe(
            'agreement-a-test-can-name: '
            . 'Move the library onto the larger disk/1/possible | '
            . 'Forget the expired credential/0/permanent',
            $which,
        );
    }
});

it('N2-R7 — work still going is its own answer', function (): void {
    $ways = everyWayOfMending(stillGoing(), AStackThatWouldMend::stillWorkingItOut(...));

    foreach ($ways as $which => $make) {
        expect(whatTheHandleSaid($make()))->toBe('still running', $which);
    }
});

it('a job the stack no longer has is ended, not unreachable and not running', function (): void {
    // The state both directions are tempting. Folded into running, a screen
    // spins on a handle nothing will ever answer for; folded into unreachable,
    // an operator is sent to look at a machine that is working perfectly and
    // said so clearly.
    $ways = everyWayOfMending(
        MockResponse::make('{"error":"no such job"}', 404),
        AStackThatWouldMend::thatForgotTheJob(...),
    );

    foreach ($ways as $which => $make) {
        expect(whatTheHandleSaid($make()))->toBe('ended', $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfMending($answered, static fn(): Mending => AStackThatWouldMend::met($why)) as $which => $make) {
            expect(whatStartingSaid($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('N1-R41 — reading a handle is a read, so reading it twice changes nothing', function (): void {
    // The distinction that lets this port exist. A job is not a pending action:
    // the stack received the action and named it, so asking after the name is a
    // read — and a read repeated is still a read. What must never repeat is the
    // *asking*, which is why the two are counted separately.
    $mending = AStackThatWouldMend::offering(theSameOffer());
    $job = Job::named(AStackThatWouldMend::THE_JOB);

    $mending->whatBecameOf(aStackThatMightMend(), theSessionARepairIsAskedWith(), $job);
    $mending->whatBecameOf(aStackThatMightMend(), theSessionARepairIsAskedWith(), $job);

    expect($mending->readings())->toBe(2)
        ->and($mending->askings())->toBe(0)
        ->and($mending->askedAfter()?->is($job))->toBeTrue()
        ->and($mending->wasGivenASession())->toBeTrue();
});

it('N1-R10 — an answer this app cannot read is the machine, not the session', function (): void {
    // Only the adapter can be asked this: the fake has no wire to malform. It
    // is here rather than in a unit test because what is being pinned is the
    // collapse — three faults with one meaning for somebody holding a phone,
    // and one that means something else entirely.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('not json at all')]);

    expect(whatStartingSaid(new Menders(new PinnedClients(), SequencedEntropy::counting())))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});

it('N1-R10 — a refused session while reading a handle is still a refused session', function (): void {
    // The reading half of the same distinction. A session that ended between
    // asking and reading is answered by signing in again, on a machine that is
    // working perfectly — and a screen holding a handle is exactly where that
    // happens, because time passes between the two calls.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('{"error":"no"}', 401)]);

    expect(whatTheHandleSaid(new Menders(new PinnedClients(), SequencedEntropy::counting())))
        ->toBe(Obstacle::CredentialWasRefused->value);
});

it('a handle that answers with something unreadable is the machine', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('not json at all')]);

    expect(whatTheHandleSaid(new Menders(new PinnedClients(), SequencedEntropy::counting())))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});

/** A yes against the listing both implementations answer with. */
function theSameYes(): Confirmed
{
    $offer = theSameOffer();

    // Walked rather than indexed, because `Repairs` publishes no index — and
    // returned from inside the loop so there is no `null` for the analyser to
    // worry about, which is the same reason `Opening::found()` walks its stacks.
    foreach ($offer->repairs() as $repair) {
        return Confirmed::against($repair, $offer, Reading::live($offer));
    }

    throw new RuntimeException('theSameOffer() holds two repairs, so this is unreachable.');
}

/**
 * The payload a stack sends once it has carried the agreement out.
 *
 * @return array<string, mixed>
 */
function whatAStackThatActedSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'repair',
        'data' => [
            'acted' => true,
            'agreement' => 'agreement-a-test-can-name',
            'beyond' => [],
            'offered' => [],
            'mended' => [
                [
                    'repair' => [
                        'check' => 'storage.one-filesystem',
                        'does' => 'Move the library onto the larger disk',
                        'effects' => ['Downloads pause while it moves'],
                        'reversible' => true,
                    ],
                    'outcome' => ['outcome' => 'fixed'],
                ],
                [
                    'repair' => [
                        'check' => 'credentials.expired',
                        'does' => 'Forget the expired credential',
                        'effects' => [],
                        'reversible' => false,
                    ],
                    'outcome' => ['outcome' => 'stopped', 'leaving' => 'Half of the library on the old disk'],
                ],
            ],
        ],
    ];
}

/** What a stack answers once it has carried the agreement out. */
function aRecordOfWhatWasDone(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackThatActedSends()));
}

/** What both implementations say a run came to. */
function theSameOutcomes(): WhatWasMended
{
    $moved = Repair::offered(
        'storage.one-filesystem',
        'Move the library onto the larger disk',
        Effects::of('Downloads pause while it moves'),
        Undoing::Possible,
    );

    $forgot = Repair::offered(
        'credentials.expired',
        'Forget the expired credential',
        Effects::nothingElse(),
        Undoing::Permanent,
    );

    return WhatWasMended::of(
        Mended::went($moved, WhatBecameOfIt::Fixed),
        Mended::stopped($forgot, LeftBehind::of('Half of the library on the old disk')),
    );
}

/** What carrying out produced, as a word, whichever arm it took. */
function whatWasDone(Mending $mending): string
{
    return $mending
        ->whatWasDoneAbout(aStackThatMightMend(), theSessionARepairIsAskedWith(), Job::named(AStackThatWouldMend::THE_JOB))
        ->either(
            stillRunning: static fn(): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay('still running'),
            done: static function (WhatWasMended $mended): WhatTheRepairTurnedOutToSay {
                $said = [];

                foreach ($mended as $one) {
                    $said[] = $one->said(
                        static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): WhatTheRepairTurnedOutToSay
                            => new WhatTheRepairTurnedOutToSay($left->either(
                                something: static fn(string $what): WhatTheRepairTurnedOutToSay
                                    => new WhatTheRepairTurnedOutToSay(sprintf('%s/%s', $became->value, $what)),
                                nothing: static fn(): WhatTheRepairTurnedOutToSay
                                    => new WhatTheRepairTurnedOutToSay($became->value),
                            )->said),
                    )->said;
                }

                return new WhatTheRepairTurnedOutToSay(sprintf('%d: %s', $mended->changed(), implode(' | ', $said)));
            },
            ended: static fn(): WhatTheRepairTurnedOutToSay => new WhatTheRepairTurnedOutToSay('ended'),
            met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay($why->value),
        )->said;
}

it('N2-R5 — agreeing is its own act, and answers a handle like every other', function (): void {
    $ways = everyWayOfMending(
        anAcknowledgement(),
        static fn(): Mending => AStackThatWouldMend::carryingOut(theSameOffer(), theSameOutcomes()),
    );

    foreach ($ways as $which => $make) {
        $mending = $make();
        $said = $mending->agreeTo(aStackThatMightMend(), theSessionARepairIsAskedWith(), theSameYes())->either(
            started: static fn(Job $job): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay(sprintf('started %s', $job->shown())),
            met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay($why->value),
        )->said;

        expect($said)->toBe(sprintf('started %s', AStackThatWouldMend::THE_JOB), $which);
    }
});

it('N2-R5 — a finished run says what became of each repair, and what it left', function (): void {
    // Per repair, because a listing agreed to as a whole comes apart: one fix
    // takes and the next stops part-way. A run reported as one word would have
    // the operator believe either that everything worked or that nothing did,
    // and there is half a library on the old disk either way.
    $ways = everyWayOfMending(
        aRecordOfWhatWasDone(),
        static fn(): Mending => AStackThatWouldMend::carryingOut(theSameOffer(), theSameOutcomes()),
    );

    foreach ($ways as $which => $make) {
        expect(whatWasDone($make()))->toBe(
            '1: fixed | stopped/Half of the library on the old disk',
            $which,
        );
    }
});

it('N2-R6 — the yes names the listing it was given', function (): void {
    // Only the fake can be asked this: what reaches the wire is the agreement's
    // name and the repair's check, and the fake is what can hold the whole
    // `Confirmed` to compare. A port that let the two be passed separately
    // could agree to a repair on behalf of a listing it was never in.
    $mending = AStackThatWouldMend::carryingOut(theSameOffer(), theSameOutcomes());
    $yes = theSameYes();

    $mending->agreeTo(aStackThatMightMend(), theSessionARepairIsAskedWith(), $yes);

    expect($mending->agreedTo()?->quoting())->toBe(theSameOffer()->named())
        ->and($mending->agreements())->toBe(1);
});

it('N1-R41 — reading what was done is a read, and does not agree again', function (): void {
    // The distinction that lets a screen hold a handle at all. An agreement
    // sent twice is a repair carried out twice, and for a fix that moves a
    // library that is not the same as doing it once.
    $mending = AStackThatWouldMend::carryingOut(theSameOffer(), theSameOutcomes());
    $job = Job::named(AStackThatWouldMend::THE_JOB);

    $mending->agreeTo(aStackThatMightMend(), theSessionARepairIsAskedWith(), theSameYes());
    $mending->whatWasDoneAbout(aStackThatMightMend(), theSessionARepairIsAskedWith(), $job);
    $mending->whatWasDoneAbout(aStackThatMightMend(), theSessionARepairIsAskedWith(), $job);

    expect($mending->agreements())->toBe(1)
        ->and($mending->readings())->toBe(2);
});

it('a job that ended after an agreement is not a run that failed', function (): void {
    // The state that matters most on this side. The operator does not know what
    // happened to their machine, and *it failed* is the one answer that is
    // certainly wrong — it may well have worked.
    $ways = everyWayOfMending(
        MockResponse::make('{"error":"no such job"}', 404),
        AStackThatWouldMend::thatForgotTheJob(...),
    );

    foreach ($ways as $which => $make) {
        expect(whatWasDone($make()))->toBe('ended', $which);
    }
});

it('N1-R10 — a refused agreement is a refused session, not a broken machine', function (): void {
    // Only the adapter can be asked this: what is being pinned is the collapse
    // on the *agreeing* half, which has its own catches.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('{"error":"no"}', 401)]);

    $said = new Menders(new PinnedClients(), SequencedEntropy::counting())
        ->agreeTo(aStackThatMightMend(), theSessionARepairIsAskedWith(), theSameYes())
        ->either(
            started: static fn(Job $job): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay(sprintf('started %s', $job->shown())),
            met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay($why->value),
        )->said;

    expect($said)->toBe(Obstacle::CredentialWasRefused->value);
});

it('an agreement the far end answers unreadably is the machine', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('not json at all')]);

    $said = new Menders(new PinnedClients(), SequencedEntropy::counting())
        ->agreeTo(aStackThatMightMend(), theSessionARepairIsAskedWith(), theSameYes())
        ->either(
            started: static fn(Job $job): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay(sprintf('started %s', $job->shown())),
            met: static fn(Obstacle $why): WhatTheRepairTurnedOutToSay
                => new WhatTheRepairTurnedOutToSay($why->value),
        )->said;

    expect($said)->toBe(Obstacle::StackDidNotAnswer->value);
});

it('N1-R10 — reading what was done can meet an obstacle of its own', function (): void {
    // Time passes between agreeing and reading, which is exactly where a
    // session ends underneath somebody.
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);

        expect(whatWasDone(new Menders(new PinnedClients(), SequencedEntropy::counting())))->toBe($why->value, $why->value);
    }
});

it('work still going on an agreement is its own answer', function (): void {
    $ways = everyWayOfMending(stillGoing(), AStackThatWouldMend::stillWorkingItOut(...));

    foreach ($ways as $which => $make) {
        expect(whatWasDone($make()))->toBe('still running', $which);
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    $payloads = [
        'the handle' => ['JobEnvelope', whatAStackNamingAJobSends()],
        'the offer' => ['RepairEnvelope', whatAStackOffering()],
        'the record' => ['RepairEnvelope', whatAStackThatActedSends()],
    ];

    foreach ($payloads as $which => [$envelope, $payload]) {
        expect(WhatTheContractAccepts::complaintsAbout($envelope, $payload))->toBe(
            [],
            sprintf("The payload this suite stands in for a stack with is not one a stack would send: %s.\n", $which),
        );
    }
});
