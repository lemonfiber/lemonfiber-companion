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
 * **The gap this guards is real and not yet closed.** The SDK exposes no
 * pinning seam: `LemonfiberConnector` overrides `resolveBaseUrl`,
 * `defaultAuth` and `defaultHeaders`, and nothing else. Saloon's
 * `defaultConfig()` reaches Guzzle's options, and Guzzle reaches curl's — but
 * `CURLOPT_PINNEDPUBLICKEY` pins the **public key**, as `sha256//` over the
 * SPKI, while {@see Modules\Kernel\Api\Fingerprint} is a SHA-256 digest of the
 * certificate. Those are different values of the same length, and passing one
 * where the other is expected fails in the most expensive way available: every
 * connection refused, against a stack that is perfectly correct.
 *
 * So closing this needs a decision rather than a patch, and it belongs with
 * `ADR-0018` rather than here. What belongs here is that nobody reaches a stack
 * in the meantime and discovers the question afterwards.
 */

/**
 * Files allowed to name the SDK's transport.
 *
 * Named by `::class` rather than as strings, which rector asks for and is right
 * about: a renamed class breaks this loudly, where a string would quietly stop
 * matching and the rule would go on reporting that nothing reaches a stack.
 *
 * Empty, and not an oversight. When the pinning adapter is written this is
 * where it gets named — one entry, so the reviewer of that change is looking at
 * this list and its reasoning at the same moment.
 */
const MAY_REACH_A_STACK = [];

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
        MAY_REACH_A_STACK,
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
