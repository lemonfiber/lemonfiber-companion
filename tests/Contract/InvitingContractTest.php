<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Ushers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatInvites;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The Inviting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `WelcomingContractTest`'s argument one conversation
// along: each act answers with a handle, and asking after the handle answers
// with the invitation.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack somebody is invited to. */
function aStackToInviteSomebodyTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The invitation both implementations answer with once the work is done. */
function theSameInvitation(): AnInvitation
{
    return AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://192.168.1.42:8096', 'The number can change'), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::NotYet,
        WhoWasTakenBack::of('bob'),
    )->granting(WhatWasGranted::granted(TheLibraries::of('Films'), WhatBecomesOfUnrated::HeldBack, WhetherTheyCanAsk::NotYet, 'A limit is not a lock', 'PG-13'));
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfTheInvitation(): array
{
    return [
        'api_version' => 1,
        'kind' => 'invitation',
        'data' => [
            'name' => 'anna',
            'address' => 'http://192.168.1.42:8096',
            'caution' => 'The number can change',
            'hours' => 72,
            'linked' => 'not-yet',
            'rehearsed' => false,
            'standing' => 'made',
            'withdrawn' => ['bob'],
            'applied' => ['libraries' => ['Films'], 'limit' => 'PG-13', 'unrated' => 'held-back', 'requesting' => 'not-yet', 'filtering' => 'A limit is not a lock'],
        ],
    ];
}

/**
 * Both ways of asking, each set up to answer the handle and then the invitation.
 *
 * @return array<string, Closure(): Inviting>
 */
function everyWayOfInviting(MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): Inviting => AStackThatInvites::answering(
            WhatBecameOfTheInvitation::underway(Job::named('j-1')),
            WhatBecameOfTheInvitation::answered(theSameInvitation()),
        ),
        'the adapter' => static function () use ($answered): Inviting {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Ushers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheInvitingCameTo
{
    public function __construct(public string $said) {}
}

/** Everything an answer says, folded to one line, so two answers can be compared. */
function everythingTheInvitingSays(WhatBecameOfTheInvitation $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatTheInvitingCameTo => new WhatTheInvitingCameTo(sprintf('underway %s', $job->shown())),
        answered: static function (AnInvitation $invitation): WhatTheInvitingCameTo {
            $toHand = $invitation->toHand();
            $withdrawn = [];

            foreach ($invitation->withdrawn() as $name) {
                $withdrawn[] = $name;
            }

            return new WhatTheInvitingCameTo(sprintf(
                '%s|%s|%s|%d|%s|%s|%s|%s|%s',
                $toHand->name(),
                $toHand->address()->url(),
                $toHand->address()->caution(),
                $toHand->hours(),
                $invitation->standing()->value,
                $invitation->linked()->value,
                $invitation->wasRehearsed() ? 'rehearsed' : 'carried out',
                implode(',', $withdrawn),
                $invitation->granted(
                    these: static fn(WhatWasGranted $granted): WhatTheInvitingCameTo => new WhatTheInvitingCameTo(sprintf('%s %s %s', $granted->limit(), $granted->unrated()->value, $granted->filtering())),
                    nothing: static fn(): WhatTheInvitingCameTo => new WhatTheInvitingCameTo('nothing'),
                )->said,
            ));
        },
        ended: static fn(): WhatTheInvitingCameTo => new WhatTheInvitingCameTo('ended'),
        refused: static fn(string $because): WhatTheInvitingCameTo => new WhatTheInvitingCameTo(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatTheInvitingCameTo => new WhatTheInvitingCameTo($why->value),
    )->said;
}

it('takes a password off, and follows the work to the invitation to hand over', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'reissue']]), 202);
    $done = MockResponse::make((string) json_encode(whatAStackSaysOfTheInvitation()));

    foreach (everyWayOfInviting($handle, $done) as $which => $make) {
        $inviting = $make();
        $started = $inviting->takeThePasswordOff(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), SomebodyInTheHousehold::called('anna'));
        $finished = $inviting->whatBecameOf(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), Job::named('j-1'));

        expect(everythingTheInvitingSays($started))->toBe('underway j-1', $which)
            ->and(everythingTheInvitingSays($finished))->toBe('anna|http://192.168.1.42:8096|The number can change|72|made|not-yet|carried out|bob|PG-13 held-back A limit is not a lock', $which);
    }
});

it('rehearses and invites, each answered with the work to follow', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'invite']]), 202);
    $asked = AnInvitationAskedFor::for('anna', TheLibraries::of('Films'));
    $offered = AnInvitation::rehearsed(AnInvitationToHand::to('anna', AnAddressToHand::at('http://192.168.1.42:8096', ''), 72), WhereTheInvitationStands::Made, WhetherTheyCanAsk::NotTried, WhoWasTakenBack::of());

    foreach (everyWayOfInviting($handle, $handle) as $which => $make) {
        $inviting = $make();

        expect(everythingTheInvitingSays($inviting->wouldInvite(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), $asked)))->toBe('underway j-1', $which);
    }

    foreach (everyWayOfInviting($handle) as $which => $make) {
        expect(everythingTheInvitingSays($make()->invite(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), AnInvitationAgreed::after($asked, $offered))))->toBe('underway j-1', $which);
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all', 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): Inviting => AStackThatInvites::met($why),
            'the adapter' => everyWayOfInviting($answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            expect(everythingTheInvitingSays($make()->takeThePasswordOff(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), SomebodyInTheHousehold::called('anna'))))
                ->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words', function (): void {
    $refused = MockResponse::make('Nobody is called bob here', 404, ['Content-Type' => 'text/plain']);
    $ways = [
        'the fake' => static fn(): Inviting => AStackThatInvites::answering(WhatBecameOfTheInvitation::refused('Nobody is called bob here')),
        'the adapter' => everyWayOfInviting($refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheInvitingSays($make()->takeThePasswordOff(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), SomebodyInTheHousehold::called('bob'))))
            ->toBe('refused Nobody is called bob here', $which);
    }
});

it('says the stack has no outcome for work it no longer knows', function (): void {
    $ways = [
        'the fake' => static fn(): Inviting => AStackThatInvites::answering(),
        'the adapter' => everyWayOfInviting(MockResponse::make('{"error":"no such job"}', 404))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheInvitingSays($make()->whatBecameOf(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))->toBe('ended', $which);
    }
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('InvitationEnvelope', whatAStackSaysOfTheInvitation()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

it('reads who is in, joined or still invited', function (): void {
    $household = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'household', 'data' => ['available' => true, 'findings' => [], 'members' => [
        ['name' => 'anna', 'access' => ['administrator' => false, 'disabled' => false, 'every_library' => true, 'libraries' => [], 'restriction' => 'unrestricted', 'unrated' => 'let-through'], 'claimed' => true, 'to_hand_over' => [], 'requests' => []],
        ['name' => 'bob', 'access' => ['administrator' => false, 'disabled' => false, 'every_library' => true, 'libraries' => [], 'restriction' => 'unrestricted', 'unrated' => 'let-through'], 'claimed' => false, 'to_hand_over' => [], 'requests' => []],
    ]]]));
    $ways = [
        'the fake' => static fn(): Inviting => AStackThatInvites::answering()->holding(TheMembers::of(AMember::joined('anna'), AMember::stillInvited('bob'))),
        'the adapter' => everyWayOfInviting($household)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        $said = $make()->whoIsIn(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'))->either(
            found: static function (TheMembers $members): WhatTheInvitingCameTo {
                $found = [];

                foreach ($members as $member) {
                    $found[] = sprintf('%s:%s', $member->name(), $member->hasJoined() ? 'joined' : 'invited');
                }

                return new WhatTheInvitingCameTo(implode(',', $found));
            },
            met: static fn(Obstacle $why): WhatTheInvitingCameTo => new WhatTheInvitingCameTo($why->value),
        )->said;

        expect($said)->toBe('anna:joined,bob:invited', $which);
    }
});

it('tells a household that could not be read from nobody in', function (): void {
    $ways = [
        'the fake' => static fn(): Inviting => AStackThatInvites::answering()->readingAs(Obstacle::StackDidNotAnswer),
        'the adapter' => everyWayOfInviting(MockResponse::make('{"error":"gone"}', 500))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        $said = $make()->whoIsIn(aStackToInviteSomebodyTo(), Session::of('a-session-not-a-secret'))->either(
            found: static fn(TheMembers $members): WhatTheInvitingCameTo => new WhatTheInvitingCameTo(sprintf('%d found', count($members))),
            met: static fn(Obstacle $why): WhatTheInvitingCameTo => new WhatTheInvitingCameTo($why->value),
        )->said;

        expect($said)->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});
