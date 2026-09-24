<?php

declare(strict_types=1);

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\WhatItKeepsOfItself;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Stacks\Api\AScreenNeedsMoreThanAStack;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\Screens;
use Tests\Support\Tree;

// Asserts on the routes the operator's and the household's providers declare,
// so their mutants are judged here: see `scripts/mutation.php`.
pest()->group(
    'holds:app-modules/operator/src/Providers/OperatorServiceProvider.php',
    'holds:app-modules/household/src/Providers/HouseholdServiceProvider.php',
);

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

it('every screen the app knows about is registered under the path it hands out', function (): void {
    // The direction that catches a builder pointing somewhere nothing serves.
    $unserved = [];

    foreach (Screens::everyPathAScreenHandsOut() as $case => $path) {
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
    //
    // Every provider, not the operator's alone. There are two surfaces now, and
    // a rule reading one of them is the same defect a second time: the module
    // it does not read is the one where a hand-spelled route would sit.
    $providers = everyProviderThatRegistersAScreen();
    $spelled = [];

    expect($providers)->not->toBe([], 'no provider registers a screen, so this rule read nothing');

    foreach ($providers as $path => $source) {
        preg_match_all("/Router::native\(\s*'([^']*)'/", $source, $found);

        foreach ($found[1] as $route) {
            $spelled[] = sprintf('%s — %s', basename($path), $route);
        }
    }

    expect($spelled)->toBe([], sprintf(
        "These routes are spelled in a provider rather than taken from `AStacksScreen` or "
        . "`AScreenWithoutAStack`:\n  %s\n\n"
        . 'A route registered by hand is a route no builder knows about, and a '
        . "builder that does not know about it cannot send anybody there.\n",
        implode("\n  ", $spelled),
    ));
});

/**
 * Every service provider that registers a screen, by the file it is in.
 *
 * Found by what they do rather than by naming the modules, so a third surface
 * is read by this the day it exists. A rule that named its subjects would be
 * green about the module nobody remembered to add.
 *
 * @return array<string, string>
 */
function everyProviderThatRegistersAScreen(): array
{
    $found = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $path) {
        $source = (string) file_get_contents($path);

        if (str_contains($source, 'Router::native(')) {
            $found[$path] = $source;
        }
    }

    return $found;
}

it('no screen spells a path the provider would have to be told about separately', function (): void {
    // The third spelling, and the one that actually bit: a template writing the
    // path into `@navigate` itself. Nothing compares a template against a
    // provider, so the button simply stops working.
    $spelled = [];

    // Every surface's views, for the reason the rule above reads every
    // provider: a second surface is a second place the same mistake fits.
    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $view) {
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
    $named = Screens::aStackInTheUri();
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
    //
    // An accessor may hand out a further set of paths rather than one —
    // `ofItself()` does, for the screens about what a machine keeps of itself
    // — and the sweep follows it, so a route moved there is still read here
    // rather than falling out of sight with the move.
    $where = WhereAStackIs::rememberedAs(Screens::aStackInTheUri());
    $unknown = [];
    $asked = 0;
    $toSweep = [$where];

    while ($toSweep !== []) {
        $holder = array_shift($toSweep);

        foreach (new ReflectionClass($holder)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            $asked++;

            $path = $method->invoke($holder);

            if ($path instanceof WhatItKeepsOfItself) {
                $toSweep[] = $path;

                continue;
            }

            // `is_string` as well as resolvable: an accessor that handed back
            // anything else is a `@navigate` with an array in it, which the
            // router cannot be asked about at all.
            if (! is_string($path) || NativeRouter::resolve($path) === null) {
                $unknown[] = sprintf('%s() hands back a path the router does not know', $method->getName());
            }
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

it('every screen that needs no machine is somewhere a template can send you too', function (): void {
    // The same question of the other destination type, because the rule above
    // names `WhereAStackIs` and that is only where a *machine's* screens are.
    // The roads into pairing are not about a machine — there is no machine yet
    // — and a rule covering one of two kinds of destination is the gap it was
    // written to close, one level up.
    //
    // These are handed out by accessors on the screen that offers them rather
    // than by a type of their own, so the case is traced to its accessor and
    // the accessor to a template.
    $linked = destinationsTemplatesNavigateTo();
    $handedOut = Screens::whatHandsOutAScreenWithoutAStack();
    $stranded = [];

    foreach (AScreenWithoutAStack::cases() as $screen) {
        // The opening screen, which the launch shows and nothing navigates to.
        // Exempt by name and said out loud: an exemption nobody can read is how
        // a rule quietly stops covering the thing it was written for.
        if ($screen === AScreenWithoutAStack::TheList) {
            continue;
        }

        $accessor = $handedOut[$screen->name] ?? null;

        if ($accessor === null) {
            $stranded[] = sprintf('%s is a screen nothing hands out a path to', $screen->name);

            continue;
        }

        if (! in_array($accessor, $linked, strict: true)) {
            $stranded[] = sprintf('%s is handed out by %s(), which no template navigates to', $screen->name, $accessor);
        }
    }

    expect($handedOut)->not->toBe([], 'no accessor was found handing out one of these, so this rule read nothing');

    expect($stranded)->toBe([], sprintf(
        "These are places the app can describe and nobody can get to:\n  %s\n\n"
        . 'On a first run the roads into pairing are the only way out of the opening screen, so one '
        . "of these with no button is an app that opens and cannot be used.\n",
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
    expect(fn(): string => AStacksScreen::Logs->forTheStack(StackId::rememberedAs(Screens::aStackInTheUri())))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'naming a machine is not enough');
});

it('a screen that needs only a stack refuses to be handed a service', function (): void {
    // The other mistake, and it is not harmless either: a caller holding a
    // service name the path does not carry has a screen that opens on whatever
    // it likes.
    expect(fn(): string => AStacksScreen::Health->forTheStacksService(
        StackId::rememberedAs(Screens::aStackInTheUri()),
        ServiceId::called('gluetun'),
    ))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');
});

it('a screen that needs only a stack refuses to be handed a form either', function (): void {
    // The same mistake by the other road. A form and a service fill the same
    // segment and are different things, which is why there are two builders —
    // and a case with nowhere to put either has to refuse both, or the one it
    // does not refuse is the one somebody reaches for.
    expect(fn(): string => AStacksScreen::Health->forTheStacksForm(
        StackId::rememberedAs(Screens::aStackInTheUri()),
        Form::called('arr'),
    ))->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');
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
        ->toBe(['Doing', 'Logs', 'WordAbout']);
});
