<?php

declare(strict_types=1);

use Tests\Support\Tree;

// C10 — an argument that cannot change the answer is a line no test can defend.
//
// `iterator_to_array($collection, preserve_keys: false)` over a collection that
// always holds a list is the shape this refuses. Both values of the argument
// produce the same array, so no test can tell the two apart and nothing on this
// side of the call is wrong if it flips. It is written for PHPStan's benefit —
// it wants a `list`, and the default gives it `array<int, T>` — which makes it
// a line that exists to satisfy a checker and is invisible to every other one.
//
// Mutation testing is what finds it, and did: `FalseToTrue` survived at both
// sites on the same afternoon, one in `YourStacks::share()` and one in
// `HowThisStackIs::findings()`. Neither was a bug. Both were a line that could
// have been a bug for as long as it stood.
//
// `WorstFirst` and `InCategory` already collect by hand for this exact reason,
// and write the reason out where they do it. The rule is the third occurrence
// deciding to stop being a convention.
//
// Narrow on purpose. `iterator_to_array` over something genuinely keyed is
// ordinary, and the argument earns itself there. What is refused is the named
// argument written *because* the checker asked, which is the only form it takes
// in this repository — `preserve_keys: false` against a collection of ours.

it('C10 — nothing passes an argument that cannot change the answer', function (): void {
    $offenders = [];

    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('native'), '.php'),
    ];

    foreach ($sources as $path) {
        // Tests may write it: a test asserting over a collection is stating the
        // shape it expects, and there the argument is the assertion.
        if (str_contains($path, '/tests/')) {
            continue;
        }

        $said = (string) file_get_contents($path);

        if (preg_match('/iterator_to_array\([^)]*preserve_keys:/', $said) === 1) {
            $offenders[] = str_replace(sprintf('%s/', Tree::root()), '', $path);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These pass `preserve_keys` to `iterator_to_array` over a collection that holds a list, "
        . "so the argument cannot be wrong and no test can defend it:\n  %s\n"
        . 'Collect by hand instead, as `WorstFirst::over()` does, and say why where you do it.',
        implode("\n  ", $offenders),
    ));
});
