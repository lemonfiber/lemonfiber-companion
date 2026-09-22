<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Http\ActionRequest;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingIsUnnamed;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Sdk\Api\Adjustments;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\WhatTheContractAccepts;

// The Adjusting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and the promise both must keep is the one the port was split
// in two for: asking what a change would do writes nothing, and agreeing to it
// writes. A fake that answered both the same way would let a screen pass that
// wrote to somebody's stack when it meant to ask.
//
// The second promise is that the difference comes back whole. A review
// carrying only the new value would be asking an operator to remember the old
// one correctly, and a stance without its refusal would say a change did not
// happen and not why.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/**
 * What a stack sends back about a proposed change.
 *
 * Every field the tests bend is an argument rather than a subscript into a
 * nested array afterwards. Building a payload and then reaching three levels
 * into it makes every one of those reads `mixed`, and the analyser is right
 * about that: the shape is only known at the point it is written down, so
 * that is where it is written down.
 *
 * `$from` is nullable rather than absent-able for the same reason it is on
 * the wire, and {@see aChangeAnsweredWithNothingHeldYet()} is the absent case.
 *
 * @return array<string, mixed>
 */
function whatAStackSendsAboutAChange(
    string $key = 'LIBRARY_PATH',
    string $from = '/data/media',
    string $to = '/data/films',
    string $cost = 'cheap',
    string $stance = 'pending',
): array {
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => [
                'change' => ['key' => $key, 'from' => $from, 'to' => $to, 'cost' => $cost],
                'stance' => $stance,
            ],
        ],
    ];
}

/**
 * The same answer with no `from` on the change at all.
 *
 * @return array<string, mixed>
 */
function aChangeAnsweredWithNothingHeldYet(): array
{
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => [
                'change' => ['key' => 'LIBRARY_PATH', 'to' => '/data/films', 'cost' => 'cheap'],
                'stance' => 'pending',
            ],
        ],
    ];
}

/**
 * The same answer with no review on it at all.
 *
 * @return array<string, mixed>
 */
function aChangeAnsweredWithNoReview(): array
{
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => ['changed' => false, 'rehearsed' => true, 'settings' => []],
    ];
}

/**
 * The machine being changed.
 *
 * Its own copy rather than the settings suite's, because the root suites share
 * one namespace and two files cannot define one name.
 */
function theMachineBeingChanged(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session the operator making the change is holding. */
function theSessionBehindAChange(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * Both ways of putting a change, each set up to produce the same answer.
 *
 * @param  array<string, mixed>  $body
 * @return array<string, Closure(): Adjusting>
 */
function everyWayOfPuttingAChange(array $body, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Adjusting => $why instanceof Obstacle
            ? AStackToldToChangeSomething::met($why)
            : AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
                ProposedChange::of(
                    'LIBRARY_PATH',
                    '/data/films',
                    WhatItHoldsNow::shown('/data/media'),
                    Cost::Cheap,
                ),
                Stance::Pending,
            )),
        'the adapter' => static function () use ($body): Adjusting {
            MockClient::destroyGlobal();
            MockClient::global([MockResponse::make($body)]);

            return new Adjustments(new PinnedClients());
        },
    ];
}

/** What a stack said about a change, as one string, whichever arm it took. */
function howAChangeReadsAsText(Adjusting $adjusting, bool $agreeing = false): string
{
    $asked = WhatToSet::to('LIBRARY_PATH', '/data/films');
    $stack = theMachineBeingChanged();
    $session = theSessionBehindAChange();

    $made = $agreeing
        ? $adjusting->agreedTo($stack, $session, $asked)
        : $adjusting->wouldBe($stack, $session, $asked);

    return $made->either(
        said: static fn(WhereTheChangeStands $stands): WhatAChangeCameBackAs
            => new WhatAChangeCameBackAs($stands->change->from->either(
                shown: static fn(string $held): WhatAChangeCameBackAs => new WhatAChangeCameBackAs(sprintf(
                    '%s:%s->%s:%s:%s',
                    $stands->change->key,
                    $held,
                    $stands->change->to,
                    $stands->change->cost->value,
                    $stands->stance->value,
                )),
                nothingYet: static fn(): WhatAChangeCameBackAs => new WhatAChangeCameBackAs(sprintf(
                    '%s:-> %s:%s:%s',
                    $stands->change->key,
                    $stands->change->to,
                    $stands->change->cost->value,
                    $stands->stance->value,
                )),
            )->said),
        refused: static fn(Obstacle $why): WhatAChangeCameBackAs
            => new WhatAChangeCameBackAs(sprintf('refused:%s', $why->value)),
    )->said;
}

/** One answer carried out of an `either()` arm. */
final readonly class WhatAChangeCameBackAs
{
    public function __construct(public string $said) {}
}

foreach (everyWayOfPuttingAChange(whatAStackSendsAboutAChange()) as $name => $build) {
    it(sprintf('%s answers with the whole difference, not just the new value', $name), function () use ($build): void {
        expect(howAChangeReadsAsText($build()))
            ->toBe('LIBRARY_PATH:/data/media->/data/films:cheap:pending');
    });
}

it('the fake and the adapter both refuse rather than answer with nothing', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(['nothing' => 'the contract knows'], 500)]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer')
        ->and(howAChangeReadsAsText(AStackToldToChangeSomething::met(Obstacle::StackDidNotAnswer)))
        ->toBe('refused:no_answer');
});

it('the fake keeps asking apart from agreeing', function (): void {
    // The whole reason the port has two methods. One argument apart on the
    // wire, and everything apart to the person whose stack it is.
    $fake = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(
        ProposedChange::of('BIND', 'lan', WhatItHoldsNow::nothingYet(), Cost::Cheap),
        Stance::Pending,
    ));

    howAChangeReadsAsText($fake);

    expect($fake->rehearsals())->toBe(1)->and($fake->writes())->toBe(0);

    howAChangeReadsAsText($fake, agreeing: true);

    expect($fake->rehearsals())->toBe(1)->and($fake->writes())->toBe(1);
});

it('a setting that holds nothing yet is not a setting that holds a blank', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(aChangeAnsweredWithNothingHeldYet())]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))
        ->toBe('LIBRARY_PATH:-> /data/films:cheap:pending');
});

it('a stance the contract has not got is refused rather than guessed at', function (): void {
    // `applied` would be the worst of the four to guess wrong, so none is
    // guessed: a word outside the set is an answer from a lemonfiber this app
    // cannot read, and saying so is truer than picking one.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSendsAboutAChange(stance: 'mostly'))]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer');
});

it('a cost the contract has not got is refused rather than read as cheap', function (): void {
    // The direction that matters: reading an unknown word as `cheap` would
    // apply a consequential change without asking anybody.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSendsAboutAChange(cost: 'free'))]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer');
});

it('an answer carrying no review at all is refused rather than read as no change', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(aChangeAnsweredWithNoReview())]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer');
});

it('a blocked change carries the reason and an applied one carries none', function (): void {
    $blocked = WhereTheChangeStands::blocked(
        ProposedChange::of('LIBRARY_PATH', '/nowhere', WhatItHoldsNow::shown('/data/media'), Cost::Consequential),
        'That path is not on a filesystem this machine can write to.',
    );
    $applied = WhereTheChangeStands::at(
        ProposedChange::of('LIBRARY_PATH', '/data/films', WhatItHoldsNow::shown('/data/media'), Cost::Cheap),
        Stance::Applied,
    );

    expect($blocked->stance)->toBe(Stance::Blocked)
        ->and($blocked->why())->toBe('That path is not on a filesystem this machine can write to.')
        ->and($applied->why())->toBe('');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ConfigEnvelope', whatAStackSendsAboutAChange()))
        ->toBe([], "The payload this suite stands in for a change with is not one a stack would send.\n");
});

it('agreeing reaches the same door with the answer read the same way', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSendsAboutAChange(stance: 'applied'))]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients()), agreeing: true))
        ->toBe('LIBRARY_PATH:/data/media->/data/films:cheap:applied');
});

it('carries the stack\'s own sentence when a change was blocked', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => [
                'change' => [
                    'key' => 'LIBRARY_PATH',
                    'from' => '/data/media',
                    'to' => '/nowhere',
                    'cost' => 'consequential',
                ],
                'stance' => 'blocked',
                'refusal' => 'That path is not on a filesystem this machine can write to.',
            ],
        ],
    ])]);

    // Read off the stance rather than off the presence of a refusal: a stack
    // that sent a sentence beside `applied` has contradicted itself, and
    // taking the sentence would put a reason under the word saying it worked.
    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))
        ->toBe('LIBRARY_PATH:/data/media->/nowhere:consequential:blocked');
});

it('a `from` that is not text is refused rather than printed', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => [
                'change' => ['key' => 'PORT', 'from' => 8443, 'to' => '8444', 'cost' => 'cheap'],
                'stance' => 'pending',
            ],
        ],
    ])]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer');
});

it('a `from` that is explicitly null is a setting holding nothing yet', function (): void {
    // Absent and null are the same answer and the contract says so, so both
    // reach the same arm — but they are two shapes on the wire and a reader
    // that handled one is not a reader that handled both.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => [
                'change' => ['key' => 'LIBRARY_PATH', 'from' => null, 'to' => '/data/films', 'cost' => 'cheap'],
                'stance' => 'pending',
            ],
        ],
    ])]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))
        ->toBe('LIBRARY_PATH:-> /data/films:cheap:pending');
});

it('a change that is not a table is refused', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => true,
            'settings' => [],
            'review' => ['change' => 'LIBRARY_PATH to /data/films', 'stance' => 'pending'],
        ],
    ])]);

    expect(howAChangeReadsAsText(new Adjustments(new PinnedClients())))->toBe('refused:no_answer');
});

it('a change naming no setting is refused rather than drawn nameless', function (): void {
    expect(static fn(): ProposedChange => ProposedChange::of(
        '   ',
        '/data/films',
        WhatItHoldsNow::nothingYet(),
        Cost::Cheap,
    ))->toThrow(SettingIsUnnamed::class);
});

it('a setting to write naming nothing is refused, and a blank value is not', function (): void {
    // The asymmetry is the point. A blank key names nothing; a blank value is
    // a setting being cleared, which an operator legitimately does.
    expect(static fn(): WhatToSet => WhatToSet::to('  ', 'x'))
        ->toThrow(SettingIsUnnamed::class)
        ->and(WhatToSet::to('LIBRARY_PATH', '')->value)->toBe('')
        // Nor is the value trimmed: a trailing space is one the operator
        // typed, and this app does not understand the value well enough to
        // correct it.
        ->and(WhatToSet::to('LIBRARY_PATH', ' /data ')->value)->toBe(' /data ');
});

// What the two methods actually put on the wire.
//
// The difference between *show me what this would do* and *do it* is one
// boolean in one request body, and nothing here was asserting it. Every case
// above drives both arms and reads what came back — which is answered by the
// stand-in response either way, so all three of these passed with the flag
// inverted, dropped, or sent as the wrong value:
//
//   wouldBe sending agreed: true      the review writes to somebody's machine
//   agreedTo sending agreed: false    agreeing does nothing and says it did
//   the key absent altogether         the stack decides, and it is not told
//
// The first of those is the one that matters. A screen whose whole promise is
// that looking costs nothing would have been writing, and every test in this
// file would still have been green.
//
// Asserted of the adapter alone, because it is the only one that sends
// anything. The fake's half of this is `rehearsals()` and `writes()`, which the
// cases above already read.

it('asks for a review without agreeing to it', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSendsAboutAChange())]);

    new Adjustments(new PinnedClients())->wouldBe(
        theMachineBeingChanged(),
        theSessionBehindAChange(),
        WhatToSet::to('LIBRARY_PATH', '/data/films'),
    );

    $sent = MockClient::getGlobal()?->getLastRequest();

    // The class as well as the body. A review sent as some other request would
    // read the same here if only the payload were checked, and the action is
    // half of what was asked.
    expect($sent)->toBeInstanceOf(ActionRequest::class)
        ->and($sent instanceof ActionRequest ? $sent->body()->all() : null)->toBe([
            'key' => 'LIBRARY_PATH',
            'value' => '/data/films',
            'agreed' => false,
        ]);
});

it('agrees to exactly what it was shown', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(whatAStackSendsAboutAChange())]);

    new Adjustments(new PinnedClients())->agreedTo(
        theMachineBeingChanged(),
        theSessionBehindAChange(),
        WhatToSet::to('LIBRARY_PATH', '/data/films'),
    );

    // The key and the value as well as the flag. Agreeing to a change the
    // review was not about is the same defect as agreeing without being asked,
    // and a test reading only the flag would not see it.
    $sent = MockClient::getGlobal()?->getLastRequest();

    expect($sent)->toBeInstanceOf(ActionRequest::class)
        ->and($sent instanceof ActionRequest ? $sent->body()->all() : null)->toBe([
            'key' => 'LIBRARY_PATH',
            'value' => '/data/films',
            'agreed' => true,
        ]);
});
