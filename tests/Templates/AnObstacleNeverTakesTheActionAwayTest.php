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
 * Every branch in a template whose condition is about meeting an obstacle.
 *
 * @return list<string>
 */
function everyObstacleBranch(string $view): array
{
    $found = [];

    // A branch runs from its own directive to the next one at any depth, which
    // is where its body ends for this purpose: anything after `@elseif` belongs
    // to a different condition, and anything after `@endif` to none.
    $pieces = preg_split('/(@(?:if|elseif|else|endif|unless|endunless|forelse|empty|endforelse)\b)/', $view, -1, PREG_SPLIT_DELIM_CAPTURE);

    if (! is_array($pieces)) {
        return [];
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

        if (! str_contains((string) preg_replace('/\).*/s', '', $body), 'met')) {
            continue;
        }

        $found[] = $body;
    }

    return $found;
}

it('N1-R3 — no obstacle branch leaves an operator with nothing to do', function (): void {
    $silent = [];
    $branches = 0;

    foreach (Tree::filesUnder(Tree::at('app-modules'), '.blade.php') as $path) {
        $view = (string) file_get_contents($path);

        foreach (everyObstacleBranch($view) as $body) {
            $branches++;

            if (str_contains($body, '@tap=') || str_contains($body, '@navigate=')) {
                continue;
            }

            $silent[] = sprintf('%s — %s', basename($path), trim((string) preg_replace('/\s+/', ' ', mb_substr($body, 0, 60))));
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
