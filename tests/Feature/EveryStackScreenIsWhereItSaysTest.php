<?php

declare(strict_types=1);

use Modules\Kernel\Api\Nonce;
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

it('every screen a stack has is registered under the path it hands out', function (): void {
    // The direction that catches a builder pointing somewhere nothing serves.
    $unserved = [];

    foreach (AStacksScreen::cases() as $screen) {
        if (NativeRouter::resolve($screen->forTheStack(aStackInTheUri())) === null) {
            $unserved[] = sprintf('%s — %s', $screen->name, $screen->value);
        }
    }

    expect($unserved)->toBe([], sprintf(
        "These screens hand out a path nothing is registered under:\n  %s\n\n"
        . 'A button navigating there does nothing, on a handset, with no error anywhere. '
        . "Register it in the operator module's provider, from this same case.\n",
        implode("\n  ", $unserved),
    ));
});

it('every stack route the provider registers is one this enum holds', function (): void {
    // The other direction, which is the one that catches a route registered by
    // hand beside the ones registered from here — the way the drift started.
    $source = file_get_contents(Tree::at('app-modules/operator/src/Providers/OperatorServiceProvider.php'));

    expect($source)->toBeString();

    preg_match_all("/Router::native\(\s*'([^']*\/stacks\/[^']*)'/", (string) $source, $spelled);

    expect($spelled[1])->toBe([], sprintf(
        "These stack routes are spelled in the provider rather than taken from `AStacksScreen`:\n  %s\n\n"
        . 'A route registered by hand is a route the builder does not know about, and a '
        . "builder that does not know about it cannot send anybody there.\n",
        implode("\n  ", $spelled[1]),
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
