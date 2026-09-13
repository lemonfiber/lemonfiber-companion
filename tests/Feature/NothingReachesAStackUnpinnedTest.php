<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Client;
use Lemonfiber\Sdk\Http\BaseUrl;
use Lemonfiber\Sdk\Http\LemonfiberConnector;
use Lemonfiber\Sdk\Http\RunToken;
use Tests\Support\Tree;

/**
 * `N1-R18`/`N1-R19`/`N1-R20` — nothing connects to a stack without pinning.
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
 * So the value is unchanged and `N1-R18` now fixes its written form — SHA-256
 * over the DER encoding, lower-case hex — which is the half of that confusion
 * no type can catch. `C6-R19` covers the cry-wolf worry from the other side:
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
    'app-modules/sdk/src/Api/PinnedClients.php' => 'ADR-0018, N1-R16, N1-R19 — the one '
        . 'file that opens a connection. It names `Client::pinnedAt()` and no other '
        . 'constructor, and takes the pin off the Stack rather than as an argument, so '
        . 'the digest is the one pairing material carried (N1-R18) and not one a caller '
        . 'supplied from somewhere else.',
];

/** What naming any of these means: this file opens a connection. */
const THE_TRANSPORT = [
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
        array_keys(MAY_REACH_A_STACK),
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
    $cited = array_filter(
        MAY_REACH_A_STACK,
        static fn(string $why): bool => preg_match('/\b(?:ADR-\d{3,4}|[A-Z]+\d*-R\d+)\b/', $why) === 1,
    );

    $bare = array_values(array_diff(array_keys(MAY_REACH_A_STACK), array_keys($cited)));

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
