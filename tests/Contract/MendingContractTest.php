<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Undoing;
use Modules\Sdk\Api\Menders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatWouldMend;

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

/** What a stack answers when it takes an action on. */
function anAcknowledgement(): MockResponse
{
    return MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'job',
            'data' => ['action' => 'repair', 'job' => AStackThatWouldMend::THE_JOB],
        ]),
        202,
    );
}

/** What a finished offer looks like on the wire. */
function anOfferedListing(): MockResponse
{
    return MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'repair',
            'data' => [
                'acted' => false,
                'agreement' => 'agreement-a-test-can-name',
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
        ]),
    );
}

/** What a stack answers while the work is still going. */
function stillGoing(): MockResponse
{
    return MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'job',
            'data' => ['action' => 'repair', 'job' => AStackThatWouldMend::THE_JOB],
        ]),
        202,
    );
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

            return new Menders(new PinnedClients());
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

    expect(whatStartingSaid(new Menders(new PinnedClients())))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});

it('N1-R10 — a refused session while reading a handle is still a refused session', function (): void {
    // The reading half of the same distinction. A session that ended between
    // asking and reading is answered by signing in again, on a machine that is
    // working perfectly — and a screen holding a handle is exactly where that
    // happens, because time passes between the two calls.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('{"error":"no"}', 401)]);

    expect(whatTheHandleSaid(new Menders(new PinnedClients())))
        ->toBe(Obstacle::CredentialWasRefused->value);
});

it('a handle that answers with something unreadable is the machine', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('not json at all')]);

    expect(whatTheHandleSaid(new Menders(new PinnedClients())))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});
