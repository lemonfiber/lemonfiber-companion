<?php

declare(strict_types=1);

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\ServiceId;
use Modules\Operator\Internal\AScreenNeedsMoreThanAStack;
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
        // Asked which builder it needs rather than told, so a case added with a
        // second placeholder is covered here without anybody remembering to add
        // it — and a case that needs one and is asked for the other refuses
        // rather than handing back a path with `{service}` still in it.
        $paths[sprintf('AStacksScreen::%s', $screen->name)] = $screen->alsoNeedsAService()
            ? $screen->forTheStacksService(aStackInTheUri(), 'gluetun')
            : $screen->forTheStack(aStackInTheUri());
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

it('every way this app asks where a machine is hands back a path the router knows', function (): void {
    // `WhereAStackIs` is the one place a stack's routes are spelled, and each
    // accessor on it is a single line — which is exactly the line that gets
    // added and never called from a test, because a template calls it and a
    // template is not executed here. `services()` arrived that way.
    //
    // Read off the type rather than listed, so the next destination is covered
    // without anybody remembering this file — the argument
    // `everyPathAScreenHandsOut()` makes about the enum, made about the type
    // that hands the enum's paths out.
    $where = WhereAStackIs::rememberedAs(aStackInTheUri());
    $unknown = [];
    $asked = 0;

    foreach (new ReflectionClass(WhereAStackIs::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
            continue;
        }

        $asked++;

        $path = $method->invoke($where);

        // `is_string` as well as resolvable: an accessor that handed back
        // anything else is a `@navigate` with an array in it, which the router
        // cannot be asked about at all.
        if (! is_string($path) || NativeRouter::resolve($path) === null) {
            $unknown[] = sprintf('%s() hands back a path the router does not know', $method->getName());
        }
    }

    // The one that needs a service, which the sweep cannot call blind.
    if (NativeRouter::resolve($where->logsOf(ServiceId::called('gluetun'))) === null) {
        $unknown[] = 'logsOf() hands back a path the router does not know';
    }

    expect($asked)->toBeGreaterThan(1, 'no accessor was read off `WhereAStackIs`, so this rule read nothing');

    expect($unknown)->toBe([], sprintf(
        "These hand out a path nothing is registered under:\n  %s\n\n"
        . 'Every one of them is a button on a handset, and a path the router does not know is a '
        . "button that does nothing with no error anywhere.\n",
        implode("\n  ", $unknown),
    ));
});

it('every way this app asks where a machine is is somewhere a template can send you', function (): void {
    // The other direction of the rule above, and the failure it cannot see.
    // That one asks whether a button points at a path the router knows; this
    // asks whether anything points at all.
    //
    // Both leave an operator unable to get somewhere, and only one of them
    // leaves a trace: a button pointing nowhere at least has a button. A
    // destination nothing navigates to is a screen that exists, is registered,
    // resolves, is covered by its own feature suite — and cannot be reached on
    // a handset, because a test constructs a screen and an operator has to tap
    // their way to one.
    //
    // `updates()` arrived exactly that way: route registered, path handed out,
    // router happy, every gate green, no way in.
    $linked = destinationsTemplatesNavigateTo();
    $stranded = [];
    $asked = 0;

    foreach (new ReflectionClass(WhereAStackIs::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic()) {
            continue;
        }

        $asked++;

        if (! in_array($method->getName(), $linked, strict: true)) {
            $stranded[] = sprintf('%s() is a destination no template navigates to', $method->getName());
        }
    }

    expect($asked)->toBeGreaterThan(1, 'no accessor was read off `WhereAStackIs`, so this rule read nothing');
    expect($linked)->not->toBe([], 'no template named a destination, so this rule read nothing');

    expect($stranded)->toBe([], sprintf(
        "These are places the app can describe and nobody can get to:\n  %s\n\n"
        . 'Put a way in on the screen it belongs under, or delete the destination. A screen '
        . "reachable only by a test is a screen that ships and never opens.\n",
        implode("\n  ", $stranded),
    ));
});

/**
 * Every destination a template navigates to, by the accessor's name.
 *
 * Read out of the templates rather than listed, so a way in that is added or
 * removed is one this rule sees without an edit — and read as `->name(` so a
 * mention of the word in a comment is not taken for a link, which is the
 * reading `K1` had to be taught about its own markers.
 *
 * @return list<string>
 */
function destinationsTemplatesNavigateTo(): array
{
    $named = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $template) {
        preg_match_all('/->([a-zA-Z]+)\(/', (string) file_get_contents($template), $found);

        foreach ($found[1] as $name) {
            $named[] = $name;
        }
    }

    return array_values(array_unique($named));
}

it('a screen that needs a service refuses to be asked for with only a stack', function (): void {
    // `str_replace` handed a pattern with a placeholder it was not given leaves
    // the placeholder in the string, and `/stacks/abc/logs/{service}` resolves
    // to nothing — a button that does nothing, on a handset, with no error
    // anywhere. That is the exact failure this enum was written to end,
    // arriving by a new road, so it is a refusal rather than a comment.
    expect(fn(): string => AStacksScreen::Logs->forTheStack(aStackInTheUri()))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'naming a machine is not enough');
});

it('a screen that needs only a stack refuses to be handed a service', function (): void {
    // The other mistake, and it is not harmless either: a caller holding a
    // service name the path does not carry has a screen that opens on whatever
    // it likes.
    expect(fn(): string => AStacksScreen::Health->forTheStacksService(aStackInTheUri(), 'gluetun'))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');
});

it('which builder a screen needs is read off its own pattern', function (): void {
    // Read rather than listed, so a case added with a second placeholder is
    // covered by both refusals without anybody remembering to add it to a list
    // — which is the failure mode a hand-kept list has, and the one this whole
    // type exists to close.
    $needingAService = array_values(array_filter(
        AStacksScreen::cases(),
        static fn(AStacksScreen $screen): bool => $screen->alsoNeedsAService(),
    ));

    expect(array_map(static fn(AStacksScreen $screen): string => $screen->name, $needingAService))
        ->toBe(['Logs']);
});
