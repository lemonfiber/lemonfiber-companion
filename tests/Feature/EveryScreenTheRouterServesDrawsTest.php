<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Contract\Api;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\ClientsThatAskWhatIsOffered;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Native\Mobile\Edge\NativeComponent;
use Saloon\Exceptions\NoMockResponseFoundException;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Tests\Support\WhatASettledScreenDraws;
use Tests\Support\WhatTheDeviceWouldDraw;
use Tests\Support\WhereAScreenCanSendYou;

// Asserts on the routes the operator's and the household's providers declare,
// so their mutants are judged here: see `scripts/mutation.php`.
pest()->group(
    'holds:app-modules/operator/src/Providers/OperatorServiceProvider.php',
    'holds:app-modules/household/src/Providers/HouseholdServiceProvider.php',
);

// F15 — every screen the router serves draws when it is drawn.
//
// `F10` and `F14` read the join between a template and its screen. `F12` reads
// the join between screens. Neither of them, and nothing else here, ever asks a
// screen to render — and rendering is where a frame is actually decided: the
// precompiler rewrites the native tags, the component tree resolves, the chrome
// is hoisted, and the screen's own state is passed to the view by the package
// rather than by anything this repository wrote.
//
// What a render catches is a template naming a variable nothing supplies:
// Blade compiles `{{ $x }}` to a bare `$x`, and an undefined one is a warning
// the walk promotes and reports. A `native:model` binding is not that any more.
// Since `nativephp/mobile` 4.5 it expands to `data_get(get_defined_vars(), …)`,
// which answers null for a name nothing supplies and raises nothing, so a
// misnamed binding draws an empty field here exactly as quietly as it would on
// a phone. `F10` reads bindings off the markup and the class instead, where the
// version of the package drawing them does not matter.
//
// So this builds each screen the way the application builds it — from the
// container, with the route's own parameters — and draws it.
//
// **With stand-ins on, because the alternative is a walk that proves less.**
// Every port answers, so each screen reaches the branch it draws when a machine
// answered rather than the one it draws when nothing did. It is also the claim
// `modules/dx` exists to make: the whole application, reachable, with no stack
// running anywhere.

/**
 * A machine to put in a route, from the stand-in the module seeded.
 *
 * Taken from the port rather than built here, so it is the stack the app would
 * actually be looking at — one assembled beside this would be a second opinion
 * about what the device holds.
 */
function theStackThisWalkLooksAt(): string
{
    foreach (app()->make(Stacks::class)->configured() as $stack) {
        return $stack->id()->stored();
    }

    throw new RuntimeException('The stand-in seeded no stack, so there is no machine to walk.');
}

/**
 * What the router was given for one screen, drawn, as it settles.
 *
 * A frame that asks for the next at once is followed by it, as on a device,
 * where a person sees that frame for as long as one round trip: a first frame
 * that asked the stack what it serves draws only that it is waiting.
 *
 * A named function because reflection and rendering both raise, and the
 * analyser refuses a checked exception inside a closure — rightly.
 *
 * @param array<string, string> $params
 */
function whatThisScreenDraws(string $class, array $params): WhatTheDeviceWouldDraw
{
    $screen = app()->make($class);

    if (! $screen instanceof NativeComponent) {
        throw new RuntimeException(sprintf('%s is served by the router and is not a screen.', $class));
    }

    if ($params !== []) {
        $screen->setParams($params);
    }

    return WhatASettledScreenDraws::of($screen);
}

/**
 * Every screen a person can be sent to, and what its route needs filling in.
 *
 * Read off the two enums rather than off the paths, because the placeholders
 * are what a screen is handed and the paths have already had them filled in.
 * `alsoNeedsAService()` is the enum's own answer, so a case added with a second
 * placeholder is covered here without anybody remembering.
 *
 * @return array<string, array<string, string>>
 */
function everyRouteTheAppServes(string $stack): array
{
    $routes = [];

    foreach (AScreenWithoutAStack::cases() as $case) {
        $routes[sprintf('AScreenWithoutAStack::%s', $case->name)] = [];
    }

    foreach (AStacksScreen::cases() as $case) {
        $routes[sprintf('AStacksScreen::%s', $case->name)] = $case->alsoNeedsAService()
            ? ['stack' => $stack, 'service' => 'gluetun']
            : ['stack' => $stack];
    }

    return $routes;
}

// F15 — every screen the router serves is built the way the app builds it, drawn, and draws something
it('every screen the router serves draws something when it is drawn', function (): void {
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $routes = everyRouteTheAppServes(theStackThisWalkLooksAt());

    // The floor, and the one that matters: a walk over no screens passes.
    expect($routes)->not->toBe([]);

    $wrong = [];

    foreach ($routes as $case => $params) {
        if (! array_key_exists($case, $served)) {
            // Not a skip. A case the router serves nothing under is a screen
            // nobody can reach, and reading it as *nothing to draw* would be
            // this walk quietly getting smaller.
            $wrong[] = sprintf('%s — the router serves nothing under it, so nothing was drawn', $case);

            continue;
        }

        try {
            $drawn = whatThisScreenDraws($served[$case], $params);
        } catch (ErrorException|Error $why) {
            // The two a *drawing* fails with, named rather than caught broadly
            // (`C6`). A promoted warning — the undefined variable this rule was
            // written for — arrives as an `ErrorException`, and Laravel wraps a
            // view's own failure in one too. A wiring failure is neither, and
            // is left to come out of this test with its own message: a screen
            // the container cannot build is not a screen that drew badly.
            $wrong[] = sprintf('%s — %s: %s', $case, $why::class, $why->getMessage());

            continue;
        }

        if ($drawn->said() === []) {
            $wrong[] = sprintf('%s — drew a frame that says nothing at all', $case);
        }
    }

    sort($wrong);

    expect($wrong)->toBe([], sprintf(
        "These are screens a person can be sent to, and this is what happened when they were drawn:\n  %s\n\n"
        . 'A screen is decided at render time — the precompiler, the component tree, the chrome, '
        . "and the state the package passes to the view. None of that is visible in the markup.\n"
        . 'An undefined variable here is a warning rather than a stop, so the frame draws with '
        . 'the field empty and nothing anywhere says why.',
        implode("\n  ", $wrong),
    ));
});

/**
 * What the stand-in answers one request with, written down where it is a read.
 *
 * A read is a request that is not an action and not a stream: an action is an
 * act of the operator's, and a stream held open is taken from rather than
 * read. Each is answered by the stand-in exactly as it would have been, and a
 * request the stand-in has no answer for as a stack that failed.
 *
 * A read the client sends again because a stack did not answer is the same
 * read, and is written down once: the client sends the one request it was
 * handed each time it tries, and a screen that reads twice hands it two.
 *
 * @param ArrayObject<int, string>     $reads
 * @param WeakMap<Request, true>       $tried every request already written down
 */
function theStandInsAnswer(PendingRequest $asked, ?MockClient $answering, ArrayObject $reads, WeakMap $tried): MockResponse|Fixture
{
    if ($asked->getMethod()->value === 'GET' && $asked->config()->get('stream') !== true && ! $tried->offsetExists($asked->getRequest())) {
        $tried[$asked->getRequest()] = true;
        $reads->append($asked->getUrl());
    }

    try {
        return $answering instanceof MockClient ? $answering->guessNextResponse($asked) : MockResponse::make('', 500);
    } catch (NoMockResponseFoundException) {
        return MockResponse::make('', 500);
    }
}

/**
 * The stand-in machines, with every read a client of theirs is sent written down.
 *
 * @param ArrayObject<int, string> $reads
 */
function clientsThatWriteDownTheirReads(Clients $standIn, ArrayObject $reads): Clients
{
    return new readonly class ($standIn, $reads) implements Clients {
        /** @var WeakMap<Request, true> */
        private WeakMap $tried;

        /** @param ArrayObject<int, string> $reads */
        public function __construct(private Clients $standIn, private ArrayObject $reads)
        {
            $this->tried = new WeakMap();
        }

        public function client(Stack $stack, Session $session): Client
        {
            return $this->writingDownWhatIsRead($this->standIn->client($stack, $session));
        }

        public function towards(Stack $stack, Session $session, Ability $path): Client
        {
            return $this->writingDownWhatIsRead($this->standIn->towards($stack, $session, $path));
        }

        public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
        {
            return $this->standIn->whatStoodInTheWay($stack, $why);
        }

        private function writingDownWhatIsRead(Client $client): Client
        {
            $answering = Closure::bind(static fn(Client $built): ?MockClient => $built->connector->getMockClient(), null, Client::class)($client);
            $reads = $this->reads;
            $tried = $this->tried;

            return $client->withMockClient(new MockClient([
                '*' => static fn(PendingRequest $asked): MockResponse|Fixture => theStandInsAnswer($asked, $answering, $reads, $tried),
            ]));
        }
    };
}

/**
 * The stand-ins switched on, behind the gate that asks each what it serves, and the list every read a client of theirs is sent is written to.
 *
 * The gate the application ships, in front of the stand-ins, so asking what a
 * stack serves is a read written down like any other: a frame that asked is a
 * frame that read.
 *
 * @return ArrayObject<int, string>
 */
function theStandInsWithTheirReadsWrittenDown(): ArrayObject
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $reads = new ArrayObject();
    $standIn = app()->make(Clients::class);
    $held = app()->make(WhatEachStackOffers::class);
    $clock = app()->make(Clock::class);
    app()->bind(Clients::class, static fn(): Clients => new ClientsThatAskWhatIsOffered(clientsThatWriteDownTheirReads($standIn, $reads), $held, $clock));

    return $reads;
}

/**
 * Every frame that read a stack more than once while the Doing screen follows a start, and every read.
 *
 * A frame the screen's own poll leads to is the poll and the render after it,
 * as the device takes them in one request. The start itself is the operator's
 * act and not a read, so the count begins on the first tick after it.
 *
 * A form the stand-in lists, because it runs no services; drawn three times
 * first, as the screen asks the stack what it serves on its first frame and
 * takes its forms a frame after what is running.
 *
 * @return array{0: list<string>, 1: list<string>}
 */
function theFramesFollowingAVerbThatReadTwice(): array
{
    $reads = theStandInsWithTheirReadsWrittenDown();
    $screen = app()->make(WhereAScreenCanSendYou::read()->screensTheRouterServes()['AStacksScreen::Doing']);

    if (! $screen instanceof WhatToDoWithThis) {
        return [['the router serves no Doing screen'], []];
    }

    $screen->setParams(['stack' => theStackThisWalkLooksAt(), 'service' => 'Id']);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->wouldYouLike(WhatToDoWithIt::Start->value);

    $twice = [];
    $all = [];

    foreach ([1, 2, 3, 4] as $frame) {
        $reads->exchangeArray([]);
        $screen->whileItSettles();
        WhatTheDeviceWouldDraw::by($screen);
        $all = [...$all, ...$reads->getArrayCopy()];

        if (count($reads) > 1) {
            $twice[] = sprintf('frame %d — %s', $frame, implode(', ', $reads->getArrayCopy()));
        }
    }

    return [$twice, $all];
}

/**
 * Every frame of a routed screen that read a stack more than once, and how many reads there were in all.
 *
 * Four frames of each, on one screen, as the device draws them while the
 * screen stays open, each opened on a stack nothing is held for. The first
 * asks the stack what it serves, and a screen with two readings takes the
 * second on a frame of its own, so the first four frames are where a second
 * read in one frame would be. A named function for {@see whatThisScreenDraws()}'s reason.
 *
 * @return array{0: list<string>, 1: int}
 */
function theFramesThatReadTwice(): array
{
    $reads = theStandInsWithTheirReadsWrittenDown();
    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $twice = [];
    $readAtAll = 0;

    foreach (everyRouteTheAppServes(theStackThisWalkLooksAt()) as $case => $params) {
        $screen = array_key_exists($case, $served) ? app()->make($served[$case]) : null;

        if (! $screen instanceof NativeComponent) {
            continue;
        }

        $screen->setParams($params);
        // Each screen opens on a stack nothing is held for, so each asks it
        // what it serves on a frame of its own.
        app()->make(WhatEachStackOffers::class)->letGoOf(StackId::rememberedAs(theStackThisWalkLooksAt()));

        foreach ([1, 2, 3, 4] as $frame) {
            $reads->exchangeArray([]);
            WhatTheDeviceWouldDraw::by($screen);
            $readAtAll += count($reads);

            if (count($reads) > 1) {
                $twice[] = sprintf('%s, frame %d — %s', $case, $frame, implode(', ', $reads->getArrayCopy()));
            }
        }
    }

    return [$twice, $readAtAll];
}

/**
 * How soon the frame a screen drew last asked to be drawn again, in milliseconds, if it asked.
 *
 * Read off the package's own record of the intervals a template declared,
 * which it keeps to itself, because that record is what wakes the next frame.
 *
 * @return list<int>
 */
function howSoonTheScreenAskedForTheNextFrame(NativeComponent $screen): array
{
    $asked = Closure::bind(static fn(NativeComponent $drawn): array => array_keys($drawn->bladePollDeadlines), null, NativeComponent::class)($screen);

    return array_values(array_filter($asked, is_int(...)));
}

/**
 * What a screen whose first frame asked the stack what it serves did wrong after it.
 *
 * @param ArrayObject<int, string> $reads
 *
 * @return list<string>
 */
function whatTheFrameAfterAskingDidWrong(string $case, NativeComponent $screen, ArrayObject $reads): array
{
    $soon = howSoonTheScreenAskedForTheNextFrame($screen);
    $reads->exchangeArray([]);
    WhatTheDeviceWouldDraw::by($screen);
    $askedAgain = array_filter($reads->getArrayCopy(), static fn(string $read): bool => str_ends_with($read, Api::CAPABILITIES_ENDPOINT));
    $wrong = [];

    if ($soon !== [16]) {
        $wrong[] = sprintf('%s — asked for the next frame in [%s] ms rather than at once', $case, implode(', ', $soon));
    }

    if ($askedAgain !== []) {
        $wrong[] = sprintf('%s — asked the stack what it serves again on the next frame', $case);
    }

    return $wrong;
}

/**
 * Every routed screen whose first frame asked the stack what it serves, and what went wrong with each that did.
 *
 * @return array{0: list<string>, 1: list<string>} the screens that asked, and what each did wrong
 */
function theFirstFramesThatAskedWhatTheStackServes(): array
{
    $reads = theStandInsWithTheirReadsWrittenDown();
    $served = WhereAScreenCanSendYou::read()->screensTheRouterServes();
    $asked = [];
    $wrong = [];

    foreach (everyRouteTheAppServes(theStackThisWalkLooksAt()) as $case => $params) {
        $screen = array_key_exists($case, $served) ? app()->make($served[$case]) : null;

        if (! $screen instanceof NativeComponent) {
            continue;
        }

        $screen->setParams($params);
        app()->make(WhatEachStackOffers::class)->letGoOf(StackId::rememberedAs(theStackThisWalkLooksAt()));
        $reads->exchangeArray([]);
        WhatTheDeviceWouldDraw::by($screen);
        $first = $reads->getArrayCopy();

        if ($first !== [] && str_ends_with($first[0], Api::CAPABILITIES_ENDPOINT)) {
            $asked[] = $case;
            $wrong = [...$wrong, ...whatTheFrameAfterAskingDidWrong($case, $screen, $reads)];
        }
    }

    return [$asked, $wrong];
}

it('asks for the next frame at once where a first frame asked the stack what it serves, and never asks it again there', function (): void {
    [$asked, $wrong] = theFirstFramesThatAskedWhatTheStackServes();

    expect(count($asked))->toBeGreaterThan(3, 'hardly a screen asked the stack what it serves on its first frame, so this rule read almost nothing')
        ->and($wrong)->toBe([], sprintf(
            "These screens waited on the stack saying what it serves:\n  %s\n\n"
            . 'The frame that asks is the frame\'s one reading, and the reading it put off is taken '
            . 'on the next frame, which is asked for at once rather than on the next look.',
            implode("\n  ", $wrong),
        ));
});

it('no frame of a screen the router serves reads a stack more than once', function (): void {
    [$twice, $readAtAll] = theFramesThatReadTwice();

    expect($readAtAll)->toBeGreaterThan(0, 'no screen read a stack, so nothing was counted')
        ->and($twice)->toBe([], sprintf(
            "These frames read a stack more than once:\n  %s\n\n"
            . 'A frame reads a stack once and draws everything from what came back. A second '
            . 'reading is taken on a frame of its own (N1-R65).',
            implode("\n  ", $twice),
        ));
});

it('no frame of the Doing screen reads a stack more than once while it follows a verb', function (): void {
    [$twice, $all] = theFramesFollowingAVerbThatReadTwice();

    $askedAfter = array_filter($all, static fn(string $read): bool => str_contains($read, '/api/jobs/'));

    expect($askedAfter)->not->toBe([], 'the start was never asked after, so nothing was counted')
        ->and($twice)->toBe([], sprintf(
            "These frames read a stack more than once while a verb was followed:\n  %s\n\n"
            . 'The poll decides from what the screen last heard, and the frame it leads to '
            . 'asks after the verb or reads what is running, not both.',
            implode("\n  ", $twice),
        ));
});
