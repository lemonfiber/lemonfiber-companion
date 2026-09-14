<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Tests\Support\Tree;

// N1-R4 and N2-R12 — the doors this application is allowed to open on a stack.
//
// Two requirements with one shape. `N1-R4` says the app must not offer first-run
// setup, and `N2-R12` says it must not offer to set or change a credential's
// value. Both are about writes, and both are stated in the spec as things a
// screen must not do — which is a rule about prose, and prose is what the
// household module's README argues cannot be checked without a rule that fires
// on its own documentation.
//
// What can be checked is the door. The SDK's client publishes nine methods and
// one of them, `act()`, takes an endpoint and a body — it is how anything on a
// stack is changed, and it is the only way this app could ever configure a
// machine or write a credential. A screen that offered setup would have to call
// it. So the rule is a list of the doors this app opens, each with the reason it
// is open, and `act()` is not among them.
//
// The list is compared against the client rather than trusted: a method renamed
// in the SDK fails here rather than leaving this rule describing a door that no
// longer exists, which is the failure mode a hand-kept list has.

/** Every method this application calls on an SDK client, and why that one. */
const DOORS_THE_APP_OPENS = [
    // `N1-R17`'s shape: a screen asks once and renders what came back. Every
    // read this app does — the doctor run, the household, what has stopped —
    // goes through this one.
    'read' => 'reads an envelope from a named endpoint, changing nothing',

    // `N2-R10`'s bounded read. Its own door rather than `read()` because the
    // answer is one document a line rather than one envelope.
    'logs' => 'reads the tail of one service, bounded and named',

    // `N2-R4` and `N2-R5`: the one thing this app can ask a stack to change,
    // and it takes a `Repair` the stack itself offered rather than an endpoint
    // and a body. A caller cannot spell an arbitrary change through it.
    'repair' => 'carries out a repair the stack offered, against the reading it was offered on',
];

/**
 * Every method the application actually calls on a client.
 *
 * @return list<string>
 */
function everyDoorTheAppOpens(): array
{
    $found = [];

    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
    ];

    foreach ($sources as $path) {
        if (str_contains($path, '/tests/')) {
            continue;
        }

        preg_match_all('/\$client->([a-zA-Z]+)\(/', (string) file_get_contents($path), $called);

        foreach ($called[1] as $door) {
            $found[$door] = true;
        }
    }

    $doors = array_keys($found);
    sort($doors);

    return $doors;
}

it('N1-R4, N2-R12 — the app opens only the doors it has a reason for', function (): void {
    $opened = everyDoorTheAppOpens();

    expect($opened)->not->toBe([], 'no call on a client was found anywhere, so this rule read nothing');

    $explained = array_map(strval(...), array_keys(DOORS_THE_APP_OPENS));
    $unexplained = array_values(array_diff($opened, $explained));

    expect($unexplained)->toBe([], sprintf(
        "These are called on a stack's client and this rule has no reason for them:\n  %s\n\n"
        . 'Every door is a thing this app can do to somebody\'s machine. `act()` in particular '
        . 'takes an endpoint and a body, which is how a stack is configured and how a credential '
        . "would be written — `N1-R4` and `N2-R12` refuse both by name.\n"
        . "Add the door here with the requirement it serves, or do not open it.\n",
        implode("\n  ", $unexplained),
    ));
});

it('every door this rule names is one the client still has', function (): void {
    // The other direction. A method renamed in the SDK would leave this rule
    // permitting a door that no longer exists and silently permitting nothing —
    // which is a rule that goes on passing while describing a contract nobody
    // speaks any more.
    $missing = [];

    foreach (array_keys(DOORS_THE_APP_OPENS) as $door) {
        if (! method_exists(Client::class, $door)) {
            $missing[] = $door;
        }
    }

    expect($missing)->toBe([], sprintf(
        "This rule names doors the SDK's client does not have:\n  %s\n",
        implode("\n  ", $missing),
    ));
});

it('N1-R4, N2-R12 — the door that writes whatever it is told is not open', function (): void {
    // Named rather than left to the list above, because this is the one that
    // matters and a reader of the list should not have to work out which. It
    // exists on the client, this app never calls it, and the day it does is the
    // day a screen can configure somebody's machine.
    // Asked by name through reflection, so a client that stopped having
    // `act()` fails here rather than leaving this case passing about a door
    // that is gone — the same both-directions shape as the rule above.
    $doors = array_map(
        static fn(ReflectionMethod $method): string => $method->getName(),
        new ReflectionClass(Client::class)->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect($doors)->toContain('act');
    expect(everyDoorTheAppOpens())->not->toContain('act');
});
