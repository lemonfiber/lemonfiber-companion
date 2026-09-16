<?php

declare(strict_types=1);

use Tests\Support\Template;

// F5 — a control drawn once per row says which row it is on.
//
// A frame is read aloud one control at a time, and the label is the whole of
// what a reader gets. Four services, each offered *Start it*, is four controls
// with one name between them: which service a control acts on is carried by
// where it sits, and position is the one thing somebody being read to cannot
// check. Three screens were doing exactly that — the services list offered
// *Start it*, *Stop it*, *Restart it* and *Read what it has been saying* once
// per row, the findings list offered *See what this service said* twice, and
// two repairs were both *Do this*.
//
// **The remedy is a name, not a layout.** `a11y-label` carries the full name
// and the button goes on drawing the short one, so the sighted reading does not
// change and the spoken one stops being ambiguous. A label built out of the
// row's own words is the same answer spelled shorter, and a loop over the verbs
// of *one* subject is not this rule's case at all — each pass draws a different
// word, which is what the check below actually asks. {@see Modules\Operator\View\Components\Action}
// takes it as `answers-to` and falls back to the drawn label, which is right
// for the ordinary case: a screen with one *Sign in* on it needs no second
// spelling of it.
//
// **Read as text, and that is this rule's limit.** A loop is an `@forelse` or
// an `@foreach` in the source; a control assembled at runtime is not one this
// can see. What it does catch is the shape the defect actually took, three
// times, which is a control written once inside a block that draws it many
// times.
//
// The composed source rather than the file, so a screen that moved its list
// into a component is a screen this still reads — `A rule that a refactor makes
// false` is the failure to avoid, and six rules in this suite have had it.

/** Where a loop opens and closes, and where a control is written. */
const WHAT_A_LOOP_IS_MADE_OF = [
    'opens' => '/@(?:forelse|foreach)\s*\(/',
    'closes' => '/@(?:endforelse|endforeach)\b/',
    'control' => '/<x-operator::action\b.*?\/>/s',
];

/**
 * Every control this template writes inside a loop, with the loop's own depth.
 *
 * One pass in offset order, because the three things being counted interleave
 * and reading them separately would say a control is inside a loop whenever the
 * file has one anywhere.
 *
 * @return list<array{at: int, tag: string}>
 */
function everyControlDrawnPerRow(string $source): array
{
    $depth = 0;
    $drawn = [];

    foreach (everyLoopAndControlIn($source) as $mark) {
        $depth += ['opens' => 1, 'closes' => -1, 'control' => 0][$mark['what']];

        if ($mark['what'] === 'control' && $depth > 0) {
            $drawn[] = ['at' => $mark['at'], 'tag' => $mark['tag']];
        }
    }

    return $drawn;
}

/**
 * Every loop opening, loop closing and control, in the order they are written.
 *
 * One list rather than three, because the counting above only works in draw
 * order: read separately, a control would be inside a loop whenever the file
 * had one anywhere.
 *
 * @return list<array{at: int, what: string, tag: string}>
 */
function everyLoopAndControlIn(string $source): array
{
    $marks = [];

    foreach (WHAT_A_LOOP_IS_MADE_OF as $what => $pattern) {
        preg_match_all($pattern, $source, $found, PREG_OFFSET_CAPTURE);

        foreach ($found[0] as [$text, $offset]) {
            $marks[] = ['at' => $offset, 'what' => $what, 'tag' => $text];
        }
    }

    usort($marks, static fn(array $one, array $other): int => $one['at'] <=> $other['at']);

    return $marks;
}

/**
 * Whether this control's name varies with the row it is drawn on.
 *
 * Two spellings, because both are right and the second reads better where it
 * fits: `answers-to` when the drawn words stay short, and a drawn label that
 * already takes the row's own words when there is room for them — a form's
 * button that says *Environment (2)* needs no second name, and giving it one
 * would be two spellings of the same sentence.
 */
function aRowsControlNamesItsRow(string $tag): bool
{
    preg_match_all('/\b(?:label|answers-to)\s*=\s*"(.*?)"/s', $tag, $named);

    // The screen itself is on every pass of the loop, so it cannot be what
    // makes a name vary; anything else in scope inside a loop came from the
    // row. That covers both spellings at once — a label built with the row's
    // own words, and a short label with an `answers-to` beside it.
    return str_contains(str_replace('$this', '', implode(' ', $named[1])), '$');
}

it('F5 — a control drawn once per row says which row it is on', function (): void {
    $unnamed = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        foreach (everyControlDrawnPerRow($template->composedSource()) as $control) {
            $looked++;

            if (! aRowsControlNamesItsRow($control['tag'])) {
                $unnamed[] = sprintf(
                    '%s:%d — %s',
                    $template->path,
                    $template->lineAt($control['at']),
                    trim(preg_replace('/\s+/', ' ', $control['tag']) ?? ''),
                );
            }
        }
    }

    expect($looked)->toBeGreaterThan(4, 'no control was found inside a loop, so this rule read nothing');

    expect($unnamed)->toBe([], sprintf(
        "These controls are drawn once per row and every one of them answers to the same name:\n  %s\n\n"
        . 'Give each an `answers-to` naming what it acts on. The drawn label stays as it is — what '
        . "changes is the name a reader hears, which is all they have to tell two rows apart.\n",
        implode("\n  ", $unnamed),
    ));
});
