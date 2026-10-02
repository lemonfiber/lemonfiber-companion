<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Sdk\Api\Clients;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;
use Saloon\Exceptions\NoMockResponseFoundException;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
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
 * What the router was given for one screen, drawn.
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

    return WhatTheDeviceWouldDraw::by($screen);
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

it('F15 — every screen the router serves draws something when it is drawn', function (): void {
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
 * @param ArrayObject<int, string> $reads
 */
function theStandInsAnswer(PendingRequest $asked, ?MockClient $answering, ArrayObject $reads): MockResponse|Fixture
{
    if ($asked->getMethod()->value === 'GET' && $asked->config()->get('stream') !== true) {
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
        /** @param ArrayObject<int, string> $reads */
        public function __construct(private Clients $standIn, private ArrayObject $reads) {}

        public function client(Stack $stack, Session $session): Client
        {
            $client = $this->standIn->client($stack, $session);
            $answering = Closure::bind(static fn(Client $built): ?MockClient => $built->connector->getMockClient(), null, Client::class)($client);
            $reads = $this->reads;

            return $client->withMockClient(new MockClient([
                '*' => static fn(PendingRequest $asked): MockResponse|Fixture => theStandInsAnswer($asked, $answering, $reads),
            ]));
        }

        public function whatStoodInTheWay(Stack $stack, Throwable $why): Obstacle
        {
            return $this->standIn->whatStoodInTheWay($stack, $why);
        }
    };
}

/**
 * The stand-ins switched on, and the list every read a client of theirs is sent is written to.
 *
 * @return ArrayObject<int, string>
 */
function theStandInsWithTheirReadsWrittenDown(): ArrayObject
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    $reads = new ArrayObject();
    $standIn = app()->make(Clients::class);
    app()->bind(Clients::class, static fn(): Clients => clientsThatWriteDownTheirReads($standIn, $reads));

    return $reads;
}

/**
 * Every frame that read a stack more than once while the Doing screen follows a start, and every read.
 *
 * A frame the screen's own poll leads to is the poll and the render after it,
 * as the device takes them in one request. The start itself is the operator's
 * act and not a read, so the count begins on the first tick after it.
 *
 * A form the stand-in lists, because it runs no services; drawn twice
 * first, as the screen takes its forms a frame after what is running.
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
 * Three frames of each, on one screen, as the device draws them while the
 * screen stays open. A screen with two readings takes the second on a frame of
 * its own, so the first three frames are where a second read in one frame
 * would be. A named function for {@see whatThisScreenDraws()}'s reason.
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

        foreach ([1, 2, 3] as $frame) {
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
