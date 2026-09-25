<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatToChange;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatWasDecided;
use Tests\Support\Tree;

// The doors this application is allowed to open on a stack.
//
// Two requirements with one shape. The app must not offer first-run
// setup, and must not offer to set or change a credential's
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
// is open.
//
// `act()` is open, and what holds the two requirements up is now this rule
// rather than the door being shut. Say that plainly: `Api::action(string)`,
// `Client::act(string)` and `WhatToDoWithIt::asked(): string` are all strings,
// so nothing in the type system stops `Api::action('setup')`. The app *does
// not* spell what goes through the door, and what makes that stay true is the
// three checks below — not the design.
//
// An action's name is the last segment of its path, and every call here
// composes that path with `Api::action()` from an `asked()` the kernel spells —
// a handful of verbs, none of them `setup` and none of them a credential. Two
// things are refused: a string handed to `Api::action()`, which would be a name
// this app chose, and a `->value` handed to it, which is the operator's word
// for a verb rather than lemonfiber's and would be refused by a machine in
// front of somebody holding a phone. The verbs are then checked against the
// list of reasons in both directions, because the forward check catches one
// that lost its reason and only the reverse catches the verb somebody adds by
// hand.
//
// The list is compared against the client rather than trusted: a method renamed
// in the SDK fails here rather than leaving this rule describing a door that no
// longer exists, which is the failure mode a hand-kept list has.

/** Every method this application calls on an SDK client, and why that one. */
const DOORS_THE_APP_OPENS = [
    // One read per frame: a screen reads once and renders what came back. Every
    // read this app does — the doctor run, the household, what has stopped —
    // goes through this one.
    'read' => 'reads an envelope from a named endpoint, changing nothing',

    // The bounded read. Its own door rather than `read()` because the
    // answer is one document a line rather than one envelope.
    'logs' => 'reads the tail of one service, bounded and named',

    // The event stream, which is where the core publishes the health summary
    // and nowhere else. It reads and changes nothing, and a screen holds it
    // only while somebody can see it.
    'eventSource' => 'listens to the stack\'s event stream for the health summary, changing nothing',

    // The one thing this app can ask a stack to change,
    // and it takes a `Repair` the stack itself offered rather than an endpoint
    // and a body. A caller cannot spell an arbitrary change through it.
    'repair' => 'carries out a repair the stack offered, against the reading it was offered on',

    // The other half of an undelivered action: work that answered with a name is asked after by
    // that name. It reads and changes nothing — a job's standing is what the
    // stack already decided — and it is how a repair's outcome arrives at all.
    // It was opened when the repairs screen was built and this rule could not see it: the
    // call is chained off `client(...)` rather than landing in a variable, and
    // the rule matched a receiver.
    'whatBecameOf' => 'asks what became of work the stack named, changing nothing',

    // The verbs below, and nothing else can reach it: the path is composed by
    // `Api::action()` from a name the kernel spells — a case of a closed set,
    // or the one agreement to take an update — which this app cannot add a
    // name to at a call site.
    'act' => 'asks for one of the verbs the list below explains, by a name the rule below holds it to',

    // The one door that does nothing to anybody's machine: it hands back the
    // client's own transport. Opened in exactly one place and for the opposite
    // of the usual reason — `Modules\Dx\Api\ClientsThatReachNothing` takes the
    // transport to put a mock under it, so that a build somebody is looking at
    // answers every screen without a stack and without a socket.
    //
    // Listed rather than exempted, because the rule is about what this app *can*
    // do and reaching a client's transport is a real capability. What bounds it
    // is the pinning rule: the transport can be taken but not built, and a request
    // written through it still carries the pin that stack was introduced under.
    'connector' => 'takes the client\'s own transport, so a stand-in can answer from the contract instead of the network',
];

/** Every verb this application can ask a stack for, and why that one. */
const VERBS_THE_APP_ASKS_FOR = [
    // The one that writes a setting, and the one this list exists to be read
    // carefully about. Unconfirmed it changes nothing and answers with the
    // review; confirmed it writes. Both go through this name, and which of
    // the two happened is decided by an argument rather than by a second
    // verb — so a reader asking *what can this app write* gets one answer
    // here rather than having to find the flag.
    //
    // It cannot reach a credential's value. The app offers a change only on a
    // setting the stack showed it a value for, and a withheld one is not a
    // value — so the control an operator would type into never appears beside
    // one. That is a property of the type the listing is read into rather
    // than of this list, which is why it is worth saying here: this line
    // would otherwise read as the app being able to set anything.
    'config-set' => 'puts a value in one setting, having first been told what that would come to',

    // A start disturbs nothing, which is why it is the one verb
    // that asks for no confirmation.
    'up' => 'starts a form, or a service inside one',

    // The verb a confirmation exists for: it takes something away from
    // everybody in the house until somebody says otherwise.
    'down' => 'stops a form, or a service inside one',

    // A stop that means to come back, and still a gap the household
    // is in — so it is asked about as the stop is.
    'restart' => 'stops and starts it again',

    // The household's two, which are not verbs about a machine at all. They settle
    // one thing somebody in the house already asked for, and they are here
    // because this list is about what may go through `Api::action()` rather
    // than about services — a second list would be a second answer to *what
    // can this app ask a stack to do*, and the two would drift.
    // This line is the requirement: a pending request is approvable from lemonfiber
    // without opening Seerr. The verb going to the stack's own endpoint is what
    // makes that true — there is no road from this app to the tool the request
    // came from, and this is the one that replaces it.
    'household-approve' => 'gives one waiting request the thing it asked for',

    // This one carries a sentence because a refusal owes the person who
    // asked a reason, and {@see \Modules\Kernel\Api\Decided} cannot be built
    // without one.
    'household-decline' => 'turns one waiting request down, with the reason it was turned down for',

    // The one verb that moves the stack onto different software. It cannot be
    // asked for bare: the only thing that spells it is an agreement naming the
    // release and the services it would change, and a release is one the stack
    // listed as waiting — so what reaches the wire is an update somebody was
    // shown and said yes to.
    'update' => 'takes a release the stack listed as waiting, against the services the operator was told it would change',

    'invite' => 'says what an invitation would grant and when it lapses, and makes the account only when that same request is agreed to',

    'reissue' => 'takes a member\'s password off so they choose the next one, naming the person and never a password',
];

/**
 * Every first argument the application hands to the writing door.
 *
 * The expression as it is written, not what it evaluates to. What this rule has
 * to know is whether a call site chose a path, and a call site that did chose it
 * in the source.
 *
 * On any receiver, for {@see everyDoorTheAppOpens()}'s reason: a call through a
 * variable named anything else is the same call. There is exactly one `->act(`
 * in this application and no class holds a client as a property, so this finds
 * the one call today and nothing else.
 *
 * @return list<string>
 */
function everyCallOnTheWritingDoor(): array
{
    $found = [];

    foreach (theApplicationsSources() as $path) {
        preg_match_all('/->act\(\s*([^,)]+)/', (string) file_get_contents($path), $called);

        foreach ($called[1] as $argument) {
            $found[] = trim($argument);
        }
    }

    return $found;
}

/**
 * Every action named to `Api::action()` by something other than `asked()`.
 *
 * Two shapes, one list: a string literal is a name this app chose, and a
 * `->value` is a verb's name in the operator's words rather than in
 * lemonfiber's. Both reach the socket as an action nobody offers.
 *
 * Read a line at a time, and comments are skipped — every docblock that
 * explains this rule names `Api::action()` while explaining it, so a reader
 * that took the whole file would find the paragraph before it found a call.
 *
 * @return list<string>
 */
function theNamesHandedToTheWritingDoor(): array
{
    $found = [];

    foreach (theApplicationsSources() as $path) {
        foreach (explode("\n", (string) file_get_contents($path)) as $line) {
            $said = trim($line);

            if (str_starts_with($said, '*') || str_starts_with($said, '//')) {
                continue;
            }

            if (! str_contains($said, 'Api::action(')) {
                continue;
            }

            if (str_contains($said, '->asked()')) {
                continue;
            }

            $found[] = sprintf('%s: %s', $path, $said);
        }
    }

    return $found;
}

/**
 * Every file of this application, leaving its tests out.
 *
 * @return list<string>
 */
function theApplicationsSources(): array
{
    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
    ];

    return array_values(array_filter(
        $sources,
        static fn(string $path): bool => ! str_contains($path, '/tests/'),
    ));
}

/**
 * An agreement to take an update, which is the only thing that spells `update`.
 *
 * Built rather than read off an enum, because there is no enum: one thing can
 * be done about an update, and the name lives on the agreement. Nothing about
 * the services changes what it is asked by, so the smallest update a reading
 * can offer is enough to ask it.
 */
function anUpdateSomebodyAgreedTo(): TakingAnUpdate
{
    return TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
    ));
}

/**
 * Every method the application actually calls on a client.
 *
 * Driven by the client's own method names rather than by a receiver named
 * `$client`, and that is the whole of whether this rule works. Matching the
 * receiver reads one spelling of a call: `$door = $client; $door->act(...)`
 * walked past every assertion here, and so did the two calls `Menders` already
 * makes — `$this->clients->client(...)->whatBecameOf(...)` is chained and never
 * lands in a variable at all, so a door this app has opened since the repairs screen was
 * built was one this rule had never seen.
 *
 * It over-approximates: a method of the same name on something that is not a
 * client counts too, and `Mending::whatBecameOf()` is one. That is the safe
 * direction — this rule can then ask for an explanation it did not need, and
 * never miss one it did. Under-approximating is what let `setup` through.
 *
 * @return list<string>
 */
function everyDoorTheAppOpens(): array
{
    $said = implode("\n", array_map(
        static fn(string $path): string => (string) file_get_contents($path),
        theApplicationsSources(),
    ));

    $found = [];

    foreach (everyDoorTheClientHas() as $door) {
        if (preg_match(sprintf('/->%s\s*\(/', preg_quote($door, '/')), $said) === 1) {
            $found[] = $door;
        }
    }

    sort($found);

    return $found;
}

/**
 * Every method the SDK's client publishes.
 *
 * Read from the class rather than listed, so a door added to the SDK is one
 * this rule can see the day it arrives — which is the half a hand-kept list
 * cannot have.
 *
 * @return list<string>
 */
function everyDoorTheClientHas(): array
{
    $found = [];

    foreach (new ReflectionClass(Client::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        // Statics are not doors. `Client::at()` builds a client rather than
        // doing anything to a stack, and what may build one is
        // `NothingOpensAConnectionByHandTest`'s question — every connection
        // through `PinnedClients`, so the certificate pin lives in one file.
        // Left in, it also reads `$stack->at()` as a door, which is an address
        // accessor with the same name and nothing to do with a client.
        if ($method->isConstructor() || $method->isStatic()) {
            continue;
        }

        $found[] = $method->getName();
    }

    return $found;
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

it('N1-R4, N2-R12 — the door that writes whatever it is told is never told a name', function (): void {
    // Named rather than left to the list above, because this is the one that
    // matters and a reader of the list should not have to work out which.
    // Asked by name through reflection, so a client that stopped having `act()`
    // fails here rather than leaving this case passing about a door that is
    // gone — the same both-directions shape as the rule above.
    $doors = array_map(
        static fn(ReflectionMethod $method): string => $method->getName(),
        new ReflectionClass(Client::class)->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect($doors)->toContain('act');

    // Every call composes its path with `Api::action()`, and none of them hands
    // that a string. A literal there is this app choosing an action's name, and
    // `setup` is a name — which is the whole of what is refused.
    $opened = everyCallOnTheWritingDoor();

    expect($opened)->not->toBe([], 'no call on the writing door was found, so this rule read nothing');
    expect(array_values(array_filter($opened, static fn(string $call): bool
        => ! str_starts_with($call, 'Api::action('))))->toBe([], sprintf(
            "These ask the writing door for a path this app spelled itself:\n  %s\n\n"
            . 'An action\'s name is the last segment of its path, so a call that does not go '
            . "through `Api::action()` with a `WhatToDoWithIt` case is a call that can name any "
            . "action a stack offers — `setup` among them (`N1-R4`), and the ones that write a "
            . "credential (`N2-R12`).\n",
            implode("\n  ", $opened),
        ));

    expect(theNamesHandedToTheWritingDoor())->toBe([], sprintf(
        "These name an action by something other than `WhatToDoWithIt::asked()`:\n  %s\n\n"
        . 'A string is a name this app chose, and a `->value` is the operator\'s word for a '
        . "verb rather than lemonfiber's — `stop` where that surface offers `down`.\n",
        implode("\n  ", theNamesHandedToTheWritingDoor()),
    ));
});

it('N2-R7 — every action this app asks for has a reason, and every reason an action', function (): void {
    // Both directions, because they catch different mistakes. The forward check
    // finds a case that lost its reason; only the reverse finds the verb
    // somebody adds to this list by hand, which is the edit that would widen
    // what this app can ask for without widening the enum.
    // Every closed set, and the one agreement that is not a set, because all
    // of them reach `Api::action()` and the rule is about that door rather
    // than about services. One left out here is a verb this app can ask for
    // and this rule does not explain, which is the shape of a rule claiming
    // more than it enforces.
    $asked = [
        ...array_map(static fn(WhatToDoWithIt $doing): string => $doing->asked(), WhatToDoWithIt::cases()),
        ...array_map(static fn(WhatWasDecided $decided): string => $decided->asked(), WhatWasDecided::cases()),
        ...array_map(static fn(WhatToChange $change): string => $change->asked(), WhatToChange::cases()),
        ...array_map(static fn(AskingThemIn $asking): string => $asking->asked(), AskingThemIn::cases()),
        anUpdateSomebodyAgreedTo()->asked(),
    ];
    $explained = array_map(strval(...), array_keys(VERBS_THE_APP_ASKS_FOR));

    sort($asked);
    sort($explained);

    expect($asked)->toBe($explained, sprintf(
        "The verbs this app can ask for and the verbs this rule explains are not the same set:\n"
        . "  asked:     %s\n  explained: %s\n",
        implode(', ', $asked),
        implode(', ', $explained),
    ));
});
