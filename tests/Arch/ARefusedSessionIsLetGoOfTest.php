<?php

declare(strict_types=1);

use Tests\Support\Tree;

// An identity removed from the household results in a signed-out app
// at the next refused call, and the app must not continue to render what was
// already loaded.
//
// The requirement has two halves and they live in different kinds of place. The
// rendering half is a value: a fold that turns a refused credential into the
// signed-out state, so nothing already loaded reaches a template. The other
// half is an effect: a session left in the store is resumed on the next frame
// and refused again, and the operator ends up looking at a sign-in prompt over
// a device that still believes it is signed in.
//
// Either half alone reads as finished. A fold that signs somebody out while the
// store keeps the session gives a screen that flips between signed-out and
// signed-in as they navigate. A store that forgets while a fold renders the
// obstacle gives a screen showing *this stack refused the pairing of this app*
// over data it loaded a moment ago — which is the sentence `N3-R13` names.
//
// So this asks for both, of every place that has one. Read as tokens: a fold by
// its `met(Obstacle` method, a screen by its `resume(` call. The declaration
// rather than the return type, because what a fold answers with is the view
// model it dresses — a different class per screen — and a rule keyed to any one
// of those names would find one fold and call it all of them.
//
// **Only the surface's own folds, not the kernel's answer types.** `WhatIsStuck`
// and `WhatWasWanted` also take an obstacle and must not ask this: they are what
// a port answers with, they are read by every surface, and a transport value
// deciding somebody is signed out would be the kernel making a decision about a
// screen. The fold is the surface's, and `E2` is what says where the line is.

// The *call*, not the name. Both of these are named in the docblocks that
// explain them, so matching the bare method would pass on a file that had lost
// the code and kept the paragraph — a rule satisfied by its own documentation,
// which is the shape this repository has paid for before.

/**
 * How a fold must turn an obstacle into a reading.
 *
 * Whether a refused credential is a signed-out app or a sentence about a
 * machine is decided in exactly one place —
 * {@see Modules\Operator\Internal\ViewModels\HowTheReadingWent::somethingStopped()}
 * — and a fold's whole part in it is to hand the obstacle over. So the question
 * this asks is not *did you ask* but *did you go through the one place that
 * asks*.
 *
 * That is the stronger of the two, and deliberately so. A rule matching the
 * `meansWeAreSignedOut()` call would pass a fold that asks and then ignores the
 * answer. A fold that goes through this cannot spell the signed-out state
 * wrongly, because it does not spell it at all.
 */
const THE_DECISION = 'HowTheReadingWent::somethingStopped($why)';

/** What a screen must do about one. */
const THE_EFFECT = '$this->letGoOfTheSession(';

/**
 * Every source file in the app holding one of these, by what it holds.
 *
 * @return array{folds: list<string>, screens: list<string>}
 */
function whatHandlesARefusal(): array
{
    $folds = [];
    $screens = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.php') as $path) {
        if (str_contains($path, '/tests/')) {
            continue;
        }

        $source = (string) file_get_contents($path);

        if (str_contains($path, '/operator/src/Internal/') && str_contains($source, 'function met(Obstacle $why)')) {
            $folds[] = $path;
        }

        // Resuming is not enough on its own: `YourStacks` resumes to report
        // whether a session is held and hands it to nothing, so there is no
        // call for a stack to refuse and nothing to let go of. What matters is
        // a screen that takes the session out and uses it, which is the arm
        // that binds one.
        if (str_contains($source, '->resume(') && str_contains($source, 'Session $session')) {
            $screens[] = $path;
        }
    }

    return ['folds' => $folds, 'screens' => $screens];
}

it('N3-R13 — every fold that renders an obstacle goes through the one place that decides', function (): void {
    $found = whatHandlesARefusal();

    expect($found['folds'])->not->toBe([], 'no fold takes an obstacle, so this rule read nothing');

    $silent = [];

    foreach ($found['folds'] as $path) {
        if (! str_contains((string) file_get_contents($path), THE_DECISION)) {
            $silent[] = basename($path);
        }
    }

    expect($silent)->toBe([], sprintf(
        "These render an obstacle without asking whether it ended the session:\n  %s\n\n"
        . '`N3-R13` says a refused credential is a signed-out app rather than a sentence '
        . "about a machine.\n"
        . 'Hand the obstacle to `HowTheReadingWent::somethingStopped()` rather than reading '
        . '`said()` and `remedy()` off it here. That is where the line is drawn, once, and '
        . 'a fold that builds the state itself is a fold that can draw it differently from '
        . "the seven beside it.\n",
        implode("\n  ", $silent),
    ));
});

it('N3-R13 — every screen that resumes a session lets go of a refused one', function (): void {
    $found = whatHandlesARefusal();

    expect($found['screens'])->not->toBe([], 'no screen resumes a session, so this rule read nothing');

    $keeping = [];

    foreach ($found['screens'] as $path) {
        if (! str_contains((string) file_get_contents($path), THE_EFFECT)) {
            $keeping[] = basename($path);
        }
    }

    expect($keeping)->toBe([], sprintf(
        "These resume a session and never let go of one the stack refused:\n  %s\n\n"
        . 'A fold cannot forget anything. A session left in the store is resumed on the next '
        . 'frame and refused again, so the operator sees a sign-in prompt over a device that '
        . "still believes it is signed in.\n",
        implode("\n  ", $keeping),
    ));
});

/**
 * How a screen must put the obstacle beside its own content.
 *
 * The component draws one arm and the screen draws the other, so the two must
 * be arms of one conditional rather than two conditionals that happen to
 * disagree in the right direction. `@else` is what makes them exclusive, and
 * Blade is what enforces it.
 *
 * `F13` says why the component cannot simply take the content as a slot and
 * choose: the slot is rendered before the component runs, so the arm it drops
 * reaches the device anyway.
 */
const THE_BRANCH = '@else';

/** @see THE_BRANCH */
const THE_QUESTION = '$this->answer()->went->cameBack()';

it('N3-R13 — a screen draws the obstacle instead of its content, never beside it', function (): void {
    $drawing = [];

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $path) {
        $source = (string) file_get_contents($path);

        if (str_contains($source, '<x-operator::what-stopped-the-reading')) {
            $drawing[$path] = $source;
        }
    }

    expect($drawing)->not->toBe([], 'no screen draws the obstacle, so this rule read nothing');

    $wrong = [];

    foreach ($drawing as $path => $source) {
        $asks = mb_strpos($source, sprintf('@if (%s)', THE_QUESTION));
        $branches = mb_strpos($source, THE_BRANCH);
        $draws = mb_strpos($source, '<x-operator::what-stopped-the-reading');

        // Positions rather than presence. A screen holding all three tokens in
        // the wrong order — the component above the question, or outside the
        // conditional entirely — draws both, which is the whole of what this
        // refuses and is exactly what a presence check would pass.
        $inOrder = is_int($asks)
            && is_int($branches)
            && is_int($draws)
            && $asks < $branches
            && $branches < $draws;

        if (! $inOrder) {
            $wrong[] = basename($path);
        }
    }

    sort($wrong);

    expect($wrong)->toBe([], sprintf(
        "These draw the obstacle without putting it opposite their own content:\n  %s\n\n"
        . 'The shape is `@if (%s)`, the screen\'s content, `@else`, the component, `@endif`. '
        . 'One conditional with two arms, so the obstacle and the reading cannot both be '
        . "drawn and cannot both be missing.\n"
        . 'A screen that guards its content separately from the component is two expressions '
        . 'of one rule, and nothing reads Blade closely enough to notice when they stop '
        . 'agreeing.',
        implode("\n  ", $wrong),
        THE_QUESTION,
    ));
});
