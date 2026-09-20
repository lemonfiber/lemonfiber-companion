<?php

declare(strict_types=1);

use Tests\Support\Calls;
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

/**
 * Whether a source hands `preserve_keys` to `iterator_to_array`.
 *
 * Read as a call rather than as a pattern, and the argument this rule is about
 * is the reason. The only call worth writing it on takes a collection, and a
 * collection arrives as `$this->findings()` at least as often as it arrives as
 * `$findings` — so a pattern stopping at the first `)` reads the second
 * spelling and walks past the first. The shape the rule exists for is a query
 * handing its own collection over, which is exactly the shape with a `)` inside
 * the call.
 */
function handsOverAKeyArgument(string $source): bool
{
    $tokens = token_get_all($source);

    return array_any($tokens, fn(string|array $token, int $at): bool => Calls::isNamed($token, 'iterator_to_array') && namesTheKeyArgument(Calls::argumentsAt($tokens, $at)));
}

/**
 * Whether one of those arguments is `preserve_keys:`.
 *
 * @param list<list<array{int, string, int}|string>> $arguments
 */
function namesTheKeyArgument(array $arguments): bool
{
    return array_any(
        $arguments,
        static fn(array $argument): bool => Calls::isNamedArgument($argument, 'preserve_keys'),
    );
}

it('C10 — nothing passes an argument that cannot change the answer', function (): void {
    $offenders = [];

    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('bridge'), '.php'),
    ];

    $read = [];

    foreach ($sources as $path) {
        // Tests may write it: a test asserting over a collection is stating the
        // shape it expects, and there the argument is the assertion.
        if (str_contains($path, '/tests/')) {
            continue;
        }

        $read[] = $path;

        if (handsOverAKeyArgument((string) file_get_contents($path))) {
            $offenders[] = str_replace(sprintf('%s/', Tree::root()), '', $path);
        }
    }

    expect($read)->not->toBe([], 'none of the three trees holds source, so this rule read nothing');

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These pass `preserve_keys` to `iterator_to_array` over a collection that holds a list, "
        . "so the argument cannot be wrong and no test can defend it:\n  %s\n"
        . 'Collect by hand instead, as `WorstFirst::over()` does, and say why where you do it.',
        implode("\n  ", $offenders),
    ));
});

it('C10 — the reading finds the argument wherever in the call it was written', function (): void {
    // The judgement, handed both spellings. A collection reaching the call as
    // its own accessor puts a `)` between the function name and the argument,
    // and that is the spelling a rule anchored on the opening bracket cannot
    // see — so it is asserted here rather than left to whichever of the two a
    // fixture happens to use.
    expect(handsOverAKeyArgument('<?php iterator_to_array($findings, preserve_keys: false);'))->toBeTrue();
    expect(handsOverAKeyArgument('<?php iterator_to_array($this->findings(), preserve_keys: false);'))->toBeTrue();

    // And the answer is not yes to everything. The call without the argument is
    // ordinary, and the argument outside a call is somebody else's word.
    expect(handsOverAKeyArgument('<?php iterator_to_array($findings);'))->toBeFalse();
    expect(handsOverAKeyArgument('<?php $preserve_keys = false;'))->toBeFalse();
});
