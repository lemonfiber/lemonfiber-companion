<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Modules\Operator\Internal\Screens\WhatElseIsRunningHere;
use Modules\Operator\Internal\Screens\WhatIsNewOnEveryStack;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Sdk\Api\EveryRequestThisAppSends;
use Modules\Wayfinding\Api\Screens\WaitsAFrameForWhatTheStackServes;
use Tests\Support\Screens;
use Tests\Support\Tree;

// Before offering an action, the app establishes whether the stack it is about
// supports it, by reading what the stack declares; never by trying the action
// and never from a version number.
//
// Two halves, and a rule for each. On the wire, every request an adapter sends
// is declared in one registry keyed by the path the stack serves it at, and
// goes through the gate that asks the stack about that path first. On the
// glass, every screen that can send an action draws the button for it through
// the one component that asks first, so no screen works the answer out for
// itself.

/**
 * The endpoint constants a stack is not asked about, each with why.
 *
 * @var array<string, string> the constant's name => why it is not a request the stack declares
 */
const WHAT_IS_NOT_ASKED_ABOUT = [
    'CAPABILITIES_ENDPOINT' => 'the declaration itself, which cannot be asked about by the answer it gives',
    'EVENTS_ENDPOINT' => 'the stream every screen hears the stack on, which is not a request the stack offers',
    'JOBS_ENDPOINT' => 'what became of work an action started, which follows a request already asked about',
    'ACTIONS_ENDPOINT' => 'where every action is composed from, and never a request on its own',
];

/**
 * The files that reach a stack's client without the gate, each with why.
 *
 * @var array<string, string> the file, from the root => why it reaches the client itself
 */
const WHAT_REACHES_A_CLIENT_ITSELF = [
    'app-modules/sdk/src/Internal/GatedClient.php' => 'is the gate, and reaches the client through it for every request a stack declares',
    'app-modules/sdk/src/Internal/TheStreamsHeld.php' => 'holds the event stream open, which is not a request the stack declares',
    'app-modules/sdk/src/Api/Assessors.php' => 'reads what the stack declares, which cannot be asked about by the answer it gives',
    'app-modules/sdk/src/Api/ClientsThatAskTheDevice.php' => 'hands on the clients it is put in front of',
    'app-modules/sdk/src/Api/ClientsThatAskWhatIsOffered.php' => 'is where the stack is asked, and hands on the clients it is put in front of',
    'app-modules/sdk/src/Api/PinnedClients.php' => 'builds the client, and hands the same one on whatever the path, because nothing here asks first',
];

/**
 * The screens that hold a port able to send an action and draw no button for one, each with why.
 *
 * @var array<class-string, string>
 */
const WHAT_OFFERS_NOTHING_ON_ITS_OWN_SCREEN = [
    WhatThisStackRuns::class => 'reads what the stack runs through the port that also starts and stops it, and leads to the screen that offers each verb',
    WhatElseIsRunningHere::class => 'reads what else runs on the machine through the port that also starts and stops a service, and offers no verb',
    WhatWouldBePutRight::class => 'its reading is the repair\'s own request, so the gate in front of the reading is the gate in front of its yes',
];

/**
 * Every source under the sdk module's published classes.
 *
 * @return array<string, string> path => source
 */
function theAdaptersSources(): array
{
    $sources = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/sdk/src/Api'), '.php') as $path) {
        $sources[$path] = (string) file_get_contents($path);
    }

    return $sources;
}

it('declares every reading an adapter sends in the registry of every request, and nothing else', function (): void {
    $sent = [];

    foreach (theAdaptersSources() as $source) {
        preg_match_all('/\bApi::([A-Z_]+_ENDPOINT)\b/', $source, $named);

        foreach ($named[1] as $constant) {
            $path = constant(sprintf('%s::%s', Api::class, $constant));

            if (is_string($path) && ! array_key_exists($constant, WHAT_IS_NOT_ASKED_ABOUT)) {
                $sent[] = $path;
            }
        }
    }

    // Logs are read through their own door, which names the endpoint inside the SDK.
    $sent[] = Api::LOGS_ENDPOINT;
    $declared = [];

    foreach (EveryRequestThisAppSends::listed() as $path) {
        if (! str_starts_with($path->named(), Api::ACTIONS_ENDPOINT)) {
            $declared[] = $path->named();
        }
    }

    $sent = array_values(array_unique($sent));
    sort($sent);
    sort($declared);

    expect($sent)->not->toBe([], 'no adapter names an endpoint, so this rule read nothing')
        ->and($declared)->toBe($sent);
});

it('sends every request through the gate that asks the stack about its path first', function (): void {
    $reaching = [];

    foreach ([...Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php')] as $path) {
        if (str_contains((string) file_get_contents($path), '->client(')) {
            $reaching[] = substr($path, strlen(Tree::root()) + 1);
        }
    }

    sort($reaching);
    $allowed = array_keys(WHAT_REACHES_A_CLIENT_ITSELF);
    sort($allowed);

    expect($reaching)->toBe($allowed, sprintf(
        "These reach a stack's client without the gate:\n  %s\n\n"
        . 'A request sent without asking the stack whether it serves the path is an action '
        . 'decided by trying it. Reach the stack through `GatedClient::of()`.',
        implode("\n  ", array_diff($reaching, $allowed)),
    ));
});

it('reads what a stack declares in one place', function (): void {
    $reading = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $path) {
        if (! str_contains($path, '/tests/') && str_contains((string) file_get_contents($path), 'Api::CAPABILITIES_ENDPOINT')) {
            $reading[] = substr($path, strlen(Tree::root()) + 1);
        }
    }

    sort($reading);

    expect($reading)->toBe([
        'app-modules/dx/src/Internal/WhatTheWireWouldAnswer.php',
        'app-modules/sdk/src/Internal/WhatEachStackOffers.php',
    ]);
});

/**
 * The ports whose adapter sends an action, by their short name.
 *
 * @return list<string>
 */
function thePortsThatSendAnAction(): array
{
    $ports = [];

    foreach (theAdaptersSources() as $source) {
        if (preg_match('/->(act|repair)\(/', $source) === 1 && preg_match('/implements ([A-Za-z, ]+)\n/', $source, $implements) === 1) {
            foreach (explode(',', $implements[1]) as $port) {
                $ports[] = trim($port);
            }
        }
    }

    return array_values(array_unique($ports));
}

it('draws every button a screen sends an action from through the component that asks the stack first', function (): void {
    $ports = thePortsThatSendAnAction();
    $acting = [];
    $silent = [];

    foreach (Screens::byTheViewTheyRender() as $view => $screen) {
        $constructor = $screen->getConstructor();
        $holds = array_map(
            static fn(ReflectionParameter $parameter): string => (string) $parameter->getType(),
            $constructor?->getParameters() ?? [],
        );

        if (array_filter($holds, static fn(string $type): bool => in_array(substr((string) strrchr(sprintf('\\%s', $type), '\\'), 1), $ports, strict: true)) === []) {
            continue;
        }

        $acting[] = $screen->getName();
        $template = (string) file_get_contents(Tree::at(Screens::theFileBehindTheView($view)));

        if (! str_contains($template, '<x-operator::offered-action') && ! array_key_exists($screen->getName(), WHAT_OFFERS_NOTHING_ON_ITS_OWN_SCREEN)) {
            $silent[] = $screen->getShortName();
        }
    }

    expect($ports)->not->toBe([], 'no adapter sends an action, so this rule read nothing')
        ->and($acting)->not->toBe([], 'no screen holds a port that sends an action, so this rule read nothing')
        ->and($silent)->toBe([], sprintf(
            "These hold a port that sends an action and draw no button through the component that asks first:\n  %s\n\n"
            . 'A button for an action is drawn as the stack offers it, and explained where it does '
            . 'not, rather than worked out by the screen. Draw it with '
            . '`<x-operator::offered-action :offer="$this->offered(...)">`.',
            implode("\n  ", $silent),
        ))
        ->and(array_diff(array_keys(WHAT_OFFERS_NOTHING_ON_ITS_OWN_SCREEN), $acting))->toBe([], 'a screen excused here holds no port that sends an action, so the excuse is stale');
});

/**
 * The ports whose adapter reads or acts on a stack through the gate, by their short name.
 *
 * @return list<string>
 */
function thePortsThatGoThroughTheGate(): array
{
    $ports = [];

    foreach (theAdaptersSources() as $source) {
        if (str_contains($source, 'GatedClient::of(') && preg_match('/implements ([A-Za-z, ]+)\n/', $source, $implements) === 1) {
            foreach (explode(',', $implements[1]) as $port) {
                $ports[] = trim($port);
            }
        }
    }

    return array_values(array_unique($ports));
}

/**
 * The screens that reach a stack through the gate and wait for it a way of their own, each with why.
 *
 * @var array<class-string, string>
 */
const WHAT_WAITS_FOR_THE_STACK_ITSELF = [
    WhatIsNewOnEveryStack::class => 'reads one stack a frame of several, so a stack that had to be asked waits its turn as any unread stack does while the others keep what they show',
];

it('draws the frame that waits for what the stack serves on every screen that asks it anything', function (): void {
    $ports = [...thePortsThatGoThroughTheGate(), 'TheWayAround', 'KnowingWhatAStackOffers'];
    $asking = [];
    $unwaited = [];

    foreach (Screens::byTheViewTheyRender() as $screen) {
        $holds = array_map(
            static fn(ReflectionParameter $parameter): string => substr((string) strrchr(sprintf('\\%s', (string) $parameter->getType()), '\\'), 1),
            $screen->getConstructor()?->getParameters() ?? [],
        );

        if (array_intersect($holds, $ports) === []) {
            continue;
        }

        $asking[] = $screen->getName();

        if (! in_array(WaitsAFrameForWhatTheStackServes::class, class_uses_recursive($screen->getName()), strict: true)
            && ! array_key_exists($screen->getName(), WHAT_WAITS_FOR_THE_STACK_ITSELF)) {
            $unwaited[] = $screen->getShortName();
        }
    }

    expect($asking)->not->toBe([], 'no screen asks a stack anything, so this rule read nothing')
        ->and($unwaited)->toBe([], sprintf(
            "These ask a stack something and never draw the frame that waits for what it serves:\n  %s\n\n"
            . 'A frame that asked the stack what it serves has read it, and the reading it was '
            . 'drawing for waits for the next frame. Without the frame that waits, the package '
            . 'draws its error instead. Take it with `FindsItsWayAround`, or `WaitsAFrameForWhatTheStackServes`.',
            implode("\n  ", $unwaited),
        ))
        ->and(array_diff(array_keys(WHAT_WAITS_FOR_THE_STACK_ITSELF), $asking))->toBe([], 'a screen excused here asks no stack anything, so the excuse is stale');
});

it('hands every offered button what the stack said', function (): void {
    $unasked = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $path) {
        $source = (string) file_get_contents($path);
        preg_match_all('/<x-operator::offered-action\b(.*?)\/>/s', $source, $buttons);

        foreach ($buttons[1] as $attributes) {
            if (! str_contains($attributes, ':offer="$this->offered(') && ! str_contains($attributes, ':offer="$offer"')) {
                $unasked[] = sprintf('%s: %s', substr($path, strlen(Tree::root()) + 1), trim((string) preg_replace('/\s+/', ' ', $attributes)));
            }
        }
    }

    expect($unasked)->toBe([], sprintf(
        "These draw an offered button from something other than what the stack said:\n  %s\n",
        implode("\n  ", $unasked),
    ));
});
