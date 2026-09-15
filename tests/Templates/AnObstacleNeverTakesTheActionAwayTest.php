<?php

declare(strict_types=1);

use Tests\Support\Tree;

// N1-R3 — the app does not hide or remove an action because the stack is
// currently unreachable; it offers the action and reports the failure.
//
// The rule reads like a rule about buttons and is really a rule about what an
// operator can do next. A screen that meets an obstacle and renders two
// sentences has taken the only action away: the stack may have woken up two
// seconds later and there is no way to find out except leaving the screen and
// coming back — which `N1-R27` names separately as the thing a screen must not
// rely on. Between them the two rules say an obstacle is a report, never a dead
// end.
//
// This was written after finding four screens that did it. `HowThisStackIs` had
// the button from the day it was written and nothing carried that forward, so
// the household screen, the stalled-downloads screen, the log window and both
// of the repair screen's obstacle branches each shipped without one. A pattern
// followed once is a pattern, and this is the rule that makes it one.
//
// Read as tokens rather than as prose: a branch is found by its `@if`/`@elseif`
// and an action by `@tap=` or `@navigate=`, in files a generator does not
// write. The extraction asserts it found branches at all, so a change to how
// these templates are shaped fails here rather than quietly matching nothing.

/**
 * The condition a branch directive opens with.
 *
 * Balanced, rather than everything before the first `)`. A condition that calls
 * anything closes a parenthesis of its own a quarter of the way in, so a reader
 * that stops there sees `($this->answer(` and no obstacle in a branch that is
 * entirely about one.
 *
 * It has to stop somewhere, though: what follows the condition is the branch
 * body, and a branch that merely mentions an obstacle is not a branch that met
 * one. The balanced group is where the condition ends whatever is written
 * inside it.
 *
 * Split by character rather than matched, because a regular expression cannot
 * balance parentheses. Bytes are enough: the two characters this counts are
 * ASCII, and anything wider passes through untouched.
 */
function theConditionIn(string $body): string
{
    $depth = 0;

    foreach (mb_str_split($body) as $at => $character) {
        $depth += $character === '(' ? 1 : 0;
        $depth -= $character === ')' ? 1 : 0;

        if ($depth === 0 && $character === ')') {
            return mb_substr($body, 0, $at + 1);
        }
    }

    return $body;
}

/**
 * Every branch in a template whose condition is about meeting an obstacle, by
 * what it does to the rest of the screen.
 *
 * An `@elseif` arm is exclusive: the other arms are what would have been drawn,
 * so whatever the operator can do next has to be inside this one. A standalone
 * `@if` is additive — it opens, closes, and the screen carries on underneath —
 * so the action it leaves them is the screen's.
 *
 * The distinction is the difference between a banner and a dead end, and it is
 * in the directive rather than in the prose beside it.
 *
 * @return array{exclusive: list<string>, additive: list<string>}
 */
function everyObstacleBranch(string $view): array
{
    $exclusive = [];
    $additive = [];

    // A branch runs from its own directive to the next one at any depth, which
    // is where its body ends for this purpose: anything after `@elseif` belongs
    // to a different condition, and anything after `@endif` to none.
    $pieces = preg_split('/(@(?:if|elseif|else|endif|unless|endunless|forelse|empty|endforelse)\b)/', $view, -1, PREG_SPLIT_DELIM_CAPTURE);

    if (! is_array($pieces)) {
        return ['exclusive' => [], 'additive' => []];
    }

    foreach ($pieces as $at => $piece) {
        if (! in_array($piece, ['@if', '@elseif'], strict: true)) {
            continue;
        }

        // A guard rather than `?? ''` on the subscript, which `C9` refuses:
        // the last piece of a split is a directive with nothing after it, and
        // that is a template ending in `@endif` rather than a body to read.
        if (! array_key_exists($at + 1, $pieces)) {
            continue;
        }

        $body = $pieces[$at + 1];

        if (! str_contains(theConditionIn($body), 'met')) {
            continue;
        }

        if ($piece === '@elseif') {
            $exclusive[] = $body;

            continue;
        }

        $additive[] = $body;
    }

    return ['exclusive' => $exclusive, 'additive' => $additive];
}

/** Whether this markup gives the operator something to press. */
function offersSomethingToDo(string $markup): bool
{
    // The component counts as much as the attributes do. An action is the thing
    // whose whole purpose is to be pressed — it carries the handler inside its
    // own template — and a rule that reads only the spelled-out attribute stops
    // seeing controls the moment they are named instead, which is a rule going
    // quiet exactly when the screens improve.
    return str_contains($markup, '@tap=')
        || str_contains($markup, '@navigate=')
        || str_contains($markup, '<x-operator::action');
}

/** Enough of a branch to find it by, on one line. */
function theBranchNamed(string $body): string
{
    return trim((string) preg_replace('/\s+/', ' ', mb_substr($body, 0, 60)));
}

it('N1-R3 — no obstacle branch leaves an operator with nothing to do', function (): void {
    $silent = [];
    $branches = 0;

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $path) {
        $view = (string) file_get_contents($path);
        $found = everyObstacleBranch($view);

        foreach ($found['exclusive'] as $body) {
            $branches++;

            if (offersSomethingToDo($body)) {
                continue;
            }

            $silent[] = sprintf('%s — %s', basename($path), theBranchNamed($body));
        }

        // An additive branch is read against the whole template, because what it
        // leaves the operator is everything it did not replace. A screen where
        // that is nothing at all is still a dead end, which is what this asks.
        foreach ($found['additive'] as $body) {
            $branches++;

            if (offersSomethingToDo($view)) {
                continue;
            }

            $silent[] = sprintf('%s — %s', basename($path), theBranchNamed($body));
        }
    }

    expect($branches)->toBeGreaterThan(0, 'no obstacle branch was found in any template, so this rule read nothing');

    expect($silent)->toBe([], sprintf(
        "These branches report an obstacle and offer nothing to do about it:\n  %s\n\n"
        . '`N1-R3` says an action is offered and the failure reported, not that the action '
        . 'goes away. A screen with two sentences and no button leaves the only way back as '
        . "leaving and returning, which `N1-R27` refuses by name.\n",
        implode("\n  ", $silent),
    ));
});
