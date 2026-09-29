<?php

declare(strict_types=1);

use Tests\Support\Tree;

// G8 — a binding is a seam only where something resolves it.
//
// The composition root binds a port to an adapter and that reads as the seam
// being in place. It is not, on its own: the seam exists where something *takes
// the port*, and a port nothing takes is a line that resolves correctly and
// changes nothing.
//
// That is not a hypothetical. `Reaching` was bound to `PinnedClients`, and
// every adapter that opens a connection took `PinnedClients` itself — a final
// class, which is a seam nothing can be put into. So `modules/dx` bound its
// stand-in at the port, every test asserting the binding passed, and with
// stand-ins on the application dialled the addresses of machines that do not
// exist. Nothing in this repository could see it: the binding was right, the
// adapters were right, and the two were not connected.
//
// So this asks the other question. Not *is it bound* — `G8` already asks that —
// but *does anything take it*.
//
// **A register rather than a refusal.** A port bound before its first consumer
// exists is ordinary and often right: the adapter is written, the screen that
// will want it is not. What is not ordinary is that state being invisible. Each
// one is named with why, the count may fall and may not rise, and an entry that
// has grown a consumer has to be taken out — which is what keeps the list from
// becoming a place ports go to be forgotten.

/**
 * Ports that are bound and that nothing takes yet.
 *
 * @var array<string, string> port => why nothing takes it
 */
const NOTHING_TAKES_IT_YET = [
    'Notifier' => 'N2-R19 — the adapter is written and local-only, and the screen that will raise '
        . 'a notification is not. Bound now so the decision that it is `PlatformNotifier` and never '
        . 'a push relay is recorded where every other binding is.',
    'Reaching' => 'A7 — the kernel\'s name for a way to reach a stack, so a capability can say it '
        . 'without naming the SDK. `Modules\Sdk\Api\Clients` extends it and is what every adapter '
        . 'that opens a connection takes, because those adapters call the client\'s own methods and '
        . 'the port answers `object`. Nothing takes the kernel\'s spelling until a capability does.',
];

/**
 * How many may be waiting.
 *
 * A ratchet rather than a budget. Lower it when the list gets shorter; it is a
 * number that may not rise, which is the whole of what makes the list mean
 * anything.
 */
const HOW_MANY_MAY_WAIT = 2;

/**
 * Every port the composition root binds, by its short name.
 *
 * Read off the root rather than off the kernel's directory, because the
 * question is about bindings: a port with no binding is `G8`'s business and a
 * class that is not a port can still be bound.
 *
 * @return list<string>
 */
function everyPortTheRootBinds(): array
{
    $said = (string) file_get_contents(Tree::at('bootstrap/Composition/CompositionRoot.php'));

    preg_match_all('/->(?:bind|singleton)\(\s*([A-Za-z_]\w*)::class/', $said, $found);

    $bound = array_values(array_unique($found[1]));

    sort($bound);

    return $bound;
}

/**
 * Whether anything in the application is handed one of these.
 *
 * The port's name followed by a variable, which is what a parameter and a
 * property both look like and what a type spelled in full looks like too — the
 * character before the name may be a backslash, and a rule that read only the
 * short spelling would answer *nothing takes it* about a port something takes.
 *
 * Wider than a constructor parameter, and deliberately: a port handed to a
 * method is still a port something takes, and narrowing would mean parsing the
 * signature rather than reading it. A `@param` line with no parameter under it
 * would count too, which is a shape `K2` already refuses.
 */
function anythingTakesThePort(string $port): bool
{
    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $path) {
        if (str_contains($path, '/tests/')) {
            continue;
        }

        if (preg_match(sprintf('/(?<!\w)%s\s+\$/', $port), (string) file_get_contents($path)) === 1) {
            return true;
        }
    }

    return false;
}

it('finds the ports the composition root binds', function (): void {
    // The floor. A reading that found none would make all three rules below
    // pass about nothing, and a root that stopped being readable is exactly the
    // state they exist for.
    expect(count(everyPortTheRootBinds()))->toBeGreaterThan(10);
});

it('G8 — every port the application binds is one something takes', function (): void {
    $decorative = [];

    foreach (everyPortTheRootBinds() as $port) {
        if (! array_key_exists($port, NOTHING_TAKES_IT_YET) && ! anythingTakesThePort($port)) {
            $decorative[] = $port;
        }
    }

    sort($decorative);

    expect($decorative)->toBe([], sprintf(
        "These are bound and nothing anywhere is handed one:\n  %s\n\n"
        . 'A binding is a seam only where something resolves it. A port nothing takes is a '
        . "line that resolves correctly and changes nothing — and anything put over it, a "
        . "stand-in most of all, is reached by nobody.\n"
        . 'Either hand it to whatever should have it, or name it in NOTHING_TAKES_IT_YET '
        . 'with the reason it is waiting (G8).',
        implode("\n  ", $decorative),
    ));
});

it('G8 — a port that has grown a consumer is taken off the waiting list', function (): void {
    // The teeth. A register whose entries are never checked is a list that
    // describes the repository as it was, and the entry that goes stale first
    // is the one somebody finally wired up — which is the moment the register
    // should get shorter rather than quietly wrong.
    $wired = [];

    foreach (array_keys(NOTHING_TAKES_IT_YET) as $port) {
        if (anythingTakesThePort($port)) {
            $wired[] = $port;
        }
    }

    sort($wired);

    expect($wired)->toBe([], sprintf(
        "These are on the waiting list and something takes them now:\n  %s\n\n"
        . "Take them out of NOTHING_TAKES_IT_YET and lower HOW_MANY_MAY_WAIT by as many.\n"
        . 'A register nobody prunes is a register that stops being read (G8).',
        implode("\n  ", $wired),
    ));
});

it('G8 — the waiting list does not grow', function (): void {
    expect(count(NOTHING_TAKES_IT_YET))->toBeLessThanOrEqual(HOW_MANY_MAY_WAIT, sprintf(
        "%d ports are bound with nothing taking them, and the ceiling is %d.\n"
        . 'The list may get shorter and may not get longer: a port bound before its first '
        . 'consumer is ordinary, and a habit of binding ports nothing takes is how a seam '
        . 'comes to be believed in rather than checked (G8).',
        count(NOTHING_TAKES_IT_YET),
        HOW_MANY_MAY_WAIT,
    ));
});
