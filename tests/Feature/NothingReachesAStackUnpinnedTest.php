<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Admission;
use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Http\BaseUrl;
use Lemonfiber\Sdk\Http\LemonfiberConnector;
use Lemonfiber\Sdk\Http\RunToken;
use Tests\Support\Tree;

/**
 * Nothing connects to a stack without pinning.
 *
 * `ADR-0018` is the whole trust model: the fingerprint comes from the pairing
 * material and never from the network, the app pins it against the stack, and
 * every later connection is checked against it **whether or not the platform's
 * trust store would accept the certificate**. A connection that skips the check
 * is not a weaker version of that design; it is the design absent.
 *
 * **This is written before the first connection exists, which is the only time
 * it is free.** Nothing in this repository builds an SDK client today. The day
 * somebody does, the honest order of work is the seam first and the request
 * second — and the way that order gets reversed is that the request is easy,
 * works immediately against a stack on the same network, and the pinning is
 * left for later. Later is after the screens are built on top of it.
 *
 * **The gap this guarded is closed, and how it closed is worth keeping.** The
 * SDK exposed no pinning seam and refused every non-loopback address, so there
 * were two walls between this application and a stack on somebody's network.
 * Both were questions for the specification rather than patches to write here —
 * [spec#332](https://github.com/lemonfiber/spec/issues/332) for the seam,
 * [spec#337](https://github.com/lemonfiber/spec/issues/337) for the address —
 * and `ADR-0025` answered them together, because neither closes alone: a seam
 * above an address check that refuses first is never reached, and a relaxed
 * address with no pin is the thing `ADR-0018` exists to prevent.
 *
 * The decision that came back is not the one that was proposed. Pinning the
 * public key instead of the certificate would survive renewal, which sounds
 * like an argument for it — and is the argument against, because `ADR-0018`
 * decided deliberately that rotation must be *loud*. What settled it was
 * measurement rather than reasoning: both digests can be enforced during the
 * handshake, so the certificate digest gives up nothing, and the option that
 * would have carried the public key is deprecated in the transport while the
 * one matching `ADR-0018` is supported.
 *
 * So the value is unchanged and its written form is fixed — SHA-256 over the
 * DER encoding, lower-case hex — which is the half of that confusion no type
 * can catch. The stack's own announcement covers the cry-wolf worry from the
 * other side:
 * the stack announces a certificate change before it happens.
 */

/**
 * Files allowed to name the SDK's transport.
 *
 * Named by `::class` rather than as strings, which rector asks for and is right
 * about: a renamed class breaks this loudly, where a string would quietly stop
 * matching and the rule would go on reporting that nothing reaches a stack.
 *
 * One entry, and it arrived the way this comment said it would: the SDK grew
 * the seam (`Client::pinnedAt()`, `BaseUrl::pinned()`, `CertificatePin`), the
 * adapter was written against it, and the reviewer of that change read this
 * list and its reasoning at the same moment.
 *
 * What the entry buys is that `PinnedClients` is the only file that can open a
 * connection, and it names one constructor. The SDK offers three: `onPort()`
 * and `at()` build a client with no pin, which is right for a surface on the
 * machine and wrong for every connection this application makes. Naming only
 * the pinned one means *reach it unpinned* has no spelling here.
 *
 * **A map rather than a list, so an entry cannot arrive without saying why.**
 * The failure this guards against is not somebody deciding to connect
 * unpinned; it is somebody adding a path here because a test was red, in a
 * change about something else. A key with no sentence beside it is a thing to
 * type past. A key that must cite the requirement or the ADR it rests on is a
 * question asked at the moment it matters, and the rule below refuses an answer
 * that cites neither.
 *
 * @var array<string, string> path => the requirement or ADR that permits it
 */
const MAY_REACH_A_STACK = [
    'app-modules/sdk/src/Api/PinnedDoors.php' => 'ADR-0018, N1-R7, N1-R19 — the one file '
        . 'that opens a door. It names `Admission::at()` and not `onPort()`, which builds one '
        . 'with no pin, and takes the digest off the Stack rather than as an argument. This is '
        . 'the transport that carries the password, so it is the last one that should ever '
        . 'reach a peer whose identity nothing established.',
    'app-modules/sdk/src/Api/PinnedClients.php' => 'ADR-0018, N1-R16, N1-R19 — the one '
    . 'file that opens a connection. It names `Client::pinnedAt()` and no other '
    . 'constructor, and takes the pin off the Stack rather than as an argument, so '
    . 'the digest is the one pairing material carried (N1-R18) and not one a caller '
    . 'supplied from somewhere else.',
];

/**
 * Files allowed to name the transport and not allowed to build one.
 *
 * A weaker permission than the one above, and the distinction is real: an
 * interface has no body, and a decorator that asks the pinning adapter for a
 * client and hands the result on opens nothing either. Neither can reach a
 * stack, and a rule that could not tell them from {@see MAY_REACH_A_STACK}
 * would have to grant both the strong permission to let either exist.
 *
 * It came up the first time something tried to stand in for the client. Every
 * adapter that opens a connection took `PinnedClients` itself — a final class,
 * which is a seam nothing can get into — so the stand-in bound at the kernel's
 * port was resolved correctly and reached by nothing, and with stand-ins on the
 * application dialled addresses that do not exist. The cure is an interface the
 * adapters take, and an interface has to say what a client is.
 *
 * **The weaker permission is enforced rather than promised.** The rule below
 * refuses a file on this list that names any way of building a client, so it
 * cannot quietly become the strong list.
 *
 * @var array<string, string> path => why naming it opens no connection
 */
const MAY_NAME_A_CLIENT = [
    'app-modules/dx/src/Internal/WhatTheWireWouldAnswer.php' => 'ADR-0018, N1-R20, Q-R72 — '
        . 'it reads one constant. `Admission::ENDPOINT` is where the door\'s path is written '
        . 'and the stand-in has to answer that path with the right envelope; copying the '
        . 'string here instead would be a second spelling that goes stale silently. It opens '
        . 'nothing: the rule below refuses this file the moment it names a constructor.',
    'app-modules/sdk/src/Api/Doors.php' => 'ADR-0018, N1-R20 — an interface, so it has no '
        . 'body to open a door with. It says what the one file that can open one answers '
        . 'with, which is what lets that file be substituted at all.',
    'app-modules/dx/src/Api/DoorsThatOpenOnNothing.php' => 'ADR-0018, N1-R20, Q-R72 — it '
        . 'builds nothing. It asks `PinnedDoors` for a door, which is therefore pinned before '
        . 'it arrives, and attaches a mock to that door\'s own connector so no request reaches '
        . 'a socket. `modules/dx` is a development dependency and `NoStandInCanReachAReleaseTest` '
        . 'is what keeps it out of a release.',
    'app-modules/sdk/src/Api/Clients.php' => 'ADR-0018, N1-R20 — an interface, so it has '
        . 'no body to open a connection with. It says what the one file that can open one '
        . 'answers with, which is what lets that file be substituted at all: without it '
        . 'every adapter names the concrete class and the seam does not exist.',
    'app-modules/dx/src/Api/ClientsThatReachNothing.php' => 'ADR-0018, N1-R20, Q-R72 — it '
        . 'builds nothing. It asks `PinnedClients` for a client, which is therefore pinned '
        . 'before it arrives, and attaches a mock to the connector so no request reaches a '
        . 'socket. It also ships nowhere: `modules/dx` is a development dependency and '
        . '`NoStandInCanReachAReleaseTest` is what keeps it out of a release.',
];

/**
 * What building one looks like, which is what the weaker list may not name.
 *
 * The SDK's three constructors and the connector's own. `Client::pinnedAt()` is
 * the only one this application may use and it is named here too: the question
 * this list answers is *did you build a client*, and building a pinned one in a
 * file that promised to build none is the same broken promise.
 */
const BUILDING_ONE = [
    'Admission::at',
    'Admission::onPort',
    'Client::pinnedAt',
    'Client::at',
    'Client::onPort',
    'new Client(',
    'new LemonfiberConnector(',
    'BaseUrl::',
];

/** What naming any of these means: this file opens a connection. */
const THE_TRANSPORT = [
    // The door as well as the client. It was absent for a long time and the
    // absence was the dangerous kind: `Admission::onPort()` builds a door with
    // no pin at all, and the one request that door carries is the operator's
    // password. A file could have opened it and no rule would have said a word.
    Admission::class,
    Client::class,
    LemonfiberConnector::class,
    BaseUrl::class,
    RunToken::class,
];

it('N1-R20 — nothing opens a connection to a stack without pinning its certificate', function (): void {
    $offenders = [];

    $files = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
    ];

    // The allowed list is subtracted rather than tested against, because an
    // empty list makes `in_array(...) === false` provably false to the
    // analyser — an error present exactly while nothing is allowed, which is
    // the state this rule is written for.
    $looking = array_values(array_diff(
        array_map(
            static fn(string $path): string => str_replace(sprintf('%s/', Tree::root()), '', $path),
            $files,
        ),
        [...array_keys(MAY_REACH_A_STACK), ...array_keys(MAY_NAME_A_CLIENT)],
    ));

    foreach ($looking as $shown) {
        if (str_contains($shown, '/tests/')) {
            continue;
        }

        $said = (string) file_get_contents(Tree::at($shown));

        foreach (THE_TRANSPORT as $named) {
            if (str_contains($said, $named)) {
                $offenders[] = sprintf('%s names %s', $shown, $named);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These reach a stack, and nothing here pins the certificate it presents:\n  %s\n\n"
        . 'ADR-0018 is the trust model: the fingerprint comes from the pairing material, '
        . 'never from the network, and every connection is checked against it whether or '
        . "not the platform's trust store would accept the certificate.\n"
        . 'The SDK exposes no seam for that — `LemonfiberConnector` overrides the base '
        . 'URL, the auth and the headers, and nothing else — so this is a decision to '
        . 'make beside ADR-0018, not a line to add here. Write the pinning adapter, name '
        . 'it in MAY_REACH_A_STACK, and the reviewer will be reading why at the same '
        . 'time (N1-R18, N1-R19, N1-R20).',
        implode("\n  ", $offenders),
    ));
});

it('N1-R20 — a file allowed to reach a stack says what permits it', function (): void {
    // The list opens exactly once, and the moment it does is the only moment
    // anybody will be looking at this file. An entry that cites nothing reads
    // as settled and is how the question gets lost.
    // Subtracted rather than looped over, for the reason the list above gives
    // about `in_array`: an empty constant makes `foreach` provably empty to the
    // analyser, which reports it — an error present exactly while nothing is
    // allowed, which is the state this rule is written for.
    $permitted = [...MAY_REACH_A_STACK, ...MAY_NAME_A_CLIENT];

    $cited = array_filter(
        $permitted,
        static fn(string $why): bool => preg_match('/\b(?:ADR-\d{3,4}|[A-Z]+\d*-R\d+)\b/', $why) === 1,
    );

    $bare = array_values(array_diff(array_keys($permitted), array_keys($cited)));

    sort($bare);

    expect($bare)->toBe([], sprintf(
        "These are allowed to reach a stack and name nothing that permits it:\n  %s\n\n"
        . 'ADR-0018 is what a pinned connection rests on, and N1-R18, N1-R19 and N1-R20 '
        . 'are what it has to satisfy. An entry citing none of them is a path somebody '
        . "added to make a red test green.\n"
        . 'If the pinning adapter is written, say so here and name the ADR it implements '
        . '(N1-R20).',
        implode("\n  ", $bare),
    ));
});

/**
 * One file's code, with its comments taken out.
 *
 * The rule below asks whether a file *builds* a client, and a comment saying
 * what a file used to build is not a file building one — the two files on the
 * weaker list both explain the change that put them there, and a text match
 * cannot tell the explanation from the thing explained.
 *
 * Tokenised rather than stripped with a pattern, because a comment can hold
 * quotes and a string can hold `/*`, and a regex that got either wrong would
 * either keep reporting prose or start ignoring code.
 */
function whatThisFileDoesRatherThanSays(string $shown): string
{
    $said = '';

    foreach (token_get_all((string) file_get_contents(Tree::at($shown))) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], strict: true)) {
            continue;
        }

        $said .= is_array($token) ? $token[1] : $token;
    }

    return $said;
}

it('N1-R20 — a file allowed only to name a client builds none', function (): void {
    // The half that keeps the weaker permission weak. Naming the type and
    // calling a constructor are different acts, and a list that permitted the
    // first would be worth nothing if it quietly permitted the second — which
    // is what it would do, silently, the day somebody added one line to a file
    // already on it.
    $building = [];

    foreach (MAY_NAME_A_CLIENT as $shown => $why) {
        $said = whatThisFileDoesRatherThanSays($shown);

        foreach (BUILDING_ONE as $how) {
            if (str_contains($said, $how)) {
                $building[] = sprintf('%s names %s, and is allowed only to name a client', $shown, $how);
            }
        }
    }

    sort($building);

    expect($building)->toBe([], sprintf(
        "These promised to build no client and name a way of building one:\n  %s\n\n"
        . 'MAY_NAME_A_CLIENT is the weaker permission: an interface with no body, and a '
        . "decorator that asks the pinning adapter for a client it did not build.\n"
        . 'A file that builds one belongs in MAY_REACH_A_STACK, where the reason it may is '
        . 'read beside ADR-0018 rather than inherited from a list it was added to for '
        . 'another reason (N1-R20).',
        implode("\n  ", $building),
    ));
});
