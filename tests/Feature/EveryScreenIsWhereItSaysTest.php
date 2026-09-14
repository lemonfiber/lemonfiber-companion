<?php

declare(strict_types=1);

use Modules\Kernel\Api\Nonce;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\AStacksScreen;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Tree;

// Both directions, the way `EveryCatalogueLineIsReadTest` and
// `EveryDerivedKeyResolvesTest` ask about a key.
//
// Before this, a route had two spellings and nothing compared them. The
// provider declared `/stacks/{stack}/repairs`; six screens spelled it again for
// themselves; and the tests held a third copy, so a test asserting a screen's
// `sprintf` against the test's own `sprintf` compared two spellings and never
// asked what was registered.
//
// A rename in the provider alone therefore left the whole suite green, the
// analyser green and the rules green, with every button on the hub pointing at
// a path nothing serves. It surfaced as an operator tapping and nothing
// happening, on a handset, at runtime — which is the one place this repository
// cannot see.

/** A stack identifier shaped the way a real one is. Named for this file (`G10`). */
function aStackInTheUri(): string
{
    return str_repeat('a', Nonce::SHORTEST);
}

/**
 * Every path the app can send anybody to, labelled by the case that hands it out.
 *
 * The two enums between them are all the screens there are — one names a machine
 * in its path and the other does not — so a screen missing from here is a screen
 * missing from an enum, which is the thing being refused.
 *
 * @return array<string, string>
 */
function everyPathAScreenHandsOut(): array
{
    $paths = [];

    foreach (AScreenWithoutAStack::cases() as $screen) {
        $paths[sprintf('AScreenWithoutAStack::%s', $screen->name)] = $screen->value;
    }

    foreach (AStacksScreen::cases() as $screen) {
        $paths[sprintf('AStacksScreen::%s', $screen->name)] = $screen->forTheStack(aStackInTheUri());
    }

    return $paths;
}

it('every screen the app knows about is registered under the path it hands out', function (): void {
    // The direction that catches a builder pointing somewhere nothing serves.
    $unserved = [];

    foreach (everyPathAScreenHandsOut() as $case => $path) {
        if (NativeRouter::resolve($path) === null) {
            $unserved[] = sprintf('%s — %s', $case, $path);
        }
    }

    expect($unserved)->toBe([], sprintf(
        "These screens hand out a path nothing is registered under:\n  %s\n\n"
        . 'A button navigating there does nothing, on a handset, with no error anywhere. '
        . "Register it in the operator module's provider, from this same case.\n",
        implode("\n  ", $unserved),
    ));
});

it('every route the provider registers is one of these enums spelled once', function (): void {
    // The other direction, which is the one that catches a route registered by
    // hand beside the ones registered from a case — the way the drift started.
    //
    // Any literal at all, not only the ones with `/stacks/` in them: the two
    // pairing roads were spelled by hand in the provider and again in
    // `your-stacks.blade.php` for as long as this test asked about stacks only,
    // which is a rule claiming more than it enforced.
    $source = file_get_contents(Tree::at('app-modules/operator/src/Providers/OperatorServiceProvider.php'));

    expect($source)->toBeString();

    preg_match_all("/Router::native\(\s*'([^']*)'/", (string) $source, $spelled);

    expect($spelled[1])->toBe([], sprintf(
        "These routes are spelled in the provider rather than taken from `AStacksScreen` or "
        . "`AScreenWithoutAStack`:\n  %s\n\n"
        . 'A route registered by hand is a route no builder knows about, and a '
        . "builder that does not know about it cannot send anybody there.\n",
        implode("\n  ", $spelled[1]),
    ));
});

it('no screen spells a path the provider would have to be told about separately', function (): void {
    // The third spelling, and the one that actually bit: a template writing the
    // path into `@navigate` itself. Nothing compares a template against a
    // provider, so the button simply stops working.
    $spelled = [];

    foreach (Tree::filesUnder(Tree::at('app-modules/operator/resources/views'), '.blade.php') as $view) {
        $body = (string) file_get_contents($view);

        if (preg_match_all("/@navigate=['\"]\s*(\/[^'\"{]*)/", $body, $found) === 0) {
            continue;
        }

        foreach ($found[1] as $path) {
            $spelled[] = sprintf('%s — %s', basename($view), $path);
        }
    }

    expect($spelled)->toBe([], sprintf(
        "These screens write a path into `@navigate` instead of asking for one:\n  %s\n\n"
        . 'Ask the screen for it, so the path comes from the same case the provider '
        . "registers.\n",
        implode("\n  ", $spelled),
    ));
});

it('the builder and the router agree about which machine a path names', function (): void {
    // Not only that the pattern resolves, but that the identifier survives it.
    // A builder that put the stack in the wrong segment would still resolve —
    // the pattern matches any single segment — and would open somebody else's
    // machine.
    $named = aStackInTheUri();
    $resolved = NativeRouter::resolve(WhereAStackIs::rememberedAs($named)->repairs());
    $params = is_array($resolved) && is_array($resolved['params'] ?? null) ? $resolved['params'] : [];

    expect($params['stack'] ?? null)->toBe($named);
});
