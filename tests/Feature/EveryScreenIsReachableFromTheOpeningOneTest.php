<?php

declare(strict_types=1);

use Tests\Support\Screens;
use Tests\Support\WhereAScreenCanSendYou;

// F12 — the walk, which is the part none of the local rules can do.
//
// Three rules already ask about reachability and all three ask it of one screen
// at a time. Every screen offers a way off it; every screen under a machine
// offers a way back to it; every destination the app can describe is one some
// template navigates to. A cluster of screens that link to each other satisfies
// every one of them: each has a way off, each has something pointing at it, and
// none of them can be reached from where a person actually starts.
//
// That is not a hypothetical shape. It is what a feature branch looks like
// halfway through — two new screens wired to each other, the button on the hub
// not written yet — and nothing between here and a handset would have said so.
//
// So this one starts at the screen a launch lands on and follows every
// `@navigate` transitively. The reachable set is the answer; a screen outside it
// is named.

it('F12 — the app opens on a screen the router serves', function (): void {
    // The entry is derived rather than named. A rule that started at
    // `your-stacks` because somebody wrote that down would go on passing on the
    // day the app opens on something else, having walked from a screen nobody
    // sees — and the screens stranded by the change are exactly the ones it
    // would still call reachable.
    $opening = WhereAScreenCanSendYou::read()->atLaunch();

    expect($opening)->not->toBe(
        '',
        "The router serves nothing under the path a launch asks for, so there is no screen "
        . "to walk from and every rule below this one is about nothing.\n"
        . 'Register the opening screen from its case in the surface module\'s provider (F12).',
    );

    expect(array_key_exists($opening, theScreensThisWalkKnows()))->toBeTrue(sprintf(
        "The screen a launch lands on is %s, and it is not one of the screens this walk "
        . "knows about.\n"
        . 'The walk would start outside its own graph and reach nothing at all (F12).',
        $opening,
    ));
});

it('F12 — every screen the router serves is one this walk knows about', function (): void {
    // The floor, and it is not a number somebody picked. Two readings find
    // screens — the router's registry, and the class discovery every module
    // rule is built on — and this rule is only as good as the second. Package
    // discovery going stale empties it silently, and an empty set of screens is
    // a set with nothing unreachable in it.
    //
    // So the registry is the floor: every screen a route resolves to has to be
    // one this walk holds. It cannot be edited down, because lowering it means
    // deleting a route.
    $known = theScreensThisWalkKnows();
    $missing = [];

    foreach (WhereAScreenCanSendYou::read()->screensTheRouterServes() as $case => $class) {
        if (! array_key_exists($class, $known)) {
            $missing[] = sprintf('%s is served under %s and this walk has never heard of it', $class, $case);
        }
    }

    expect($known)->not->toBe([], 'no screen was paired with a view, so this rule read nothing');

    expect($missing)->toBe([], sprintf(
        "The router serves these and the walk does not hold them:\n  %s\n\n"
        . 'A screen the walk does not hold is a screen it can neither reach nor report, '
        . "so the answer below is about a smaller application than the one that ships.\n",
        implode("\n  ", $missing),
    ));
});

it('F12 — every way off a screen names a screen this rule can follow', function (): void {
    // An edge that cannot be resolved is the one failure this rule must never
    // absorb. Dropped quietly it goes one of two ways, and the second is the
    // one that matters: a screen reached only through the dropped edge is
    // reported as stranded when it is not, and — since a dropped edge is also a
    // missing way *out* — a cluster hanging off it is reported as reachable
    // when nobody can get there.
    //
    // Either way the rule is wrong while staying green, which is the condition
    // it exists to refuse. So it says what it could not read, and by name.
    ['unreadable' => $unreadable] = WhereAScreenCanSendYou::read()->everyNavigation();

    expect($unreadable)->toBe([], sprintf(
        "These ways off a screen could not be followed to one:\n  %s\n\n"
        . 'Every `@navigate` is an accessor answering with a path, and the accessor is '
        . 'followed to the case it hands out and the case to the screen the router serves. '
        . "One that answers some other way is an edge this rule cannot see (F12).\n",
        implode("\n  ", $unreadable),
    ));

    expect(anAccessorNamingTwoScreens())->toBe([], sprintf(
        "These accessor names mean two different screens:\n  %s\n\n"
        . 'A template names an accessor and never the type declaring it, so two types '
        . 'answering differently under one name make every `@navigate` calling it '
        . "ambiguous — and the walk would take whichever was read last (F12).\n",
        implode("\n  ", anAccessorNamingTwoScreens()),
    ));
});

it('F12 — the walk crosses at least one edge for every screen but the first', function (): void {
    // The other floor, derived the same way. A graph in which every screen is
    // reachable from one opening screen has an edge arriving at each of the
    // others, so there are at least as many edges as there are screens after
    // the first. Fewer than that and the reading has gone quiet — a renamed
    // attribute, a template moved somewhere this cannot find it — whatever the
    // walk then says about who can be reached.
    //
    // It rises with every screen added and falls only when one is deleted,
    // which is the property a floor somebody wrote down by hand does not have.
    $screens = theScreensThisWalkKnows();
    ['steps' => $steps] = WhereAScreenCanSendYou::read()->everyNavigation();

    expect(count($steps))->toBeGreaterThanOrEqual(count($screens) - 1, sprintf(
        "This walk read %d ways off a screen across %d screens, and a graph where every "
        . "screen is reachable has at least %d.\n"
        . 'Whatever it says about reachability below, it is not reading the templates (F12).',
        count($steps),
        count($screens),
        count($screens) - 1,
    ));
});

it('F12 — every screen can be reached from the one the app opens on', function (): void {
    // The rule. Everything above is about whether it is reading anything.
    $where = WhereAScreenCanSendYou::read();
    $screens = theScreensThisWalkKnows();
    ['steps' => $steps] = $where->everyNavigation();

    $reached = $where->reachedFrom($where->atLaunch(), $steps);
    $stranded = [];

    foreach ($screens as $class => $view) {
        if (! in_array($class, $reached, strict: true)) {
            $stranded[] = sprintf('%s — %s', $view, $class);
        }
    }

    expect($stranded)->toBe([], sprintf(
        "No sequence of taps from the opening screen arrives at these:\n  %s\n\n"
        . 'Each of them resolves, renders, has a way off it and has something pointing at '
        . 'it — which is every rule this repository had before this one, and none of them '
        . "is the same question as whether a person can get there.\nPut a way in on a "
        . "screen that can be reached, or delete the screen.\n",
        implode("\n  ", $stranded),
    ));

    // The same answer counted rather than listed, which is what fails when the
    // walk reaches a screen twice under two names and the list above therefore
    // reads as empty.
    expect($reached)->toHaveCount(count($screens), sprintf(
        "%d of %d screens can be reached by tapping from the opening one (F12).",
        count($reached),
        count($screens),
    ));
});

/**
 * Every screen this walk holds, by its class, against the view it renders.
 *
 * The walk's own node set, kept the other way up from {@see Screens} because
 * every answer here is about a class: a step names the class it leaves and the
 * class it arrives at, and the view is what a reader needs to find the file.
 *
 * @return array<string, string>
 */
function theScreensThisWalkKnows(): array
{
    $found = [];

    foreach (Screens::byTheViewTheyRender() as $view => $screen) {
        $found[$screen->getName()] = $view;
    }

    return $found;
}

/**
 * Accessor names that two types answer differently.
 *
 * @return list<string>
 */
function anAccessorNamingTwoScreens(): array
{
    $answered = [];
    $ambiguous = [];

    foreach (Screens::accessorsHandingOutAScreen() as $handout) {
        $before = $answered[$handout['accessor']] ?? $handout['screen'];

        if ($before !== $handout['screen']) {
            $ambiguous[] = sprintf('%s() means %s and %s', $handout['accessor'], $before, $handout['screen']);
        }

        $answered[$handout['accessor']] = $handout['screen'];
    }

    return $ambiguous;
}
