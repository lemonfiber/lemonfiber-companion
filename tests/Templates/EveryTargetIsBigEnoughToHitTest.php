<?php

declare(strict_types=1);

use Tests\Support\Template;

// A target is bigger than the words on it.
//
// The platform paints one button style, so a screen with a second thing to
// offer draws it as text that is tapped. What that costs is a target the size
// of the words: a line of note text with eight points of padding is about
// thirty, against the 44 iOS asks for and the 48 Android does — and these are
// the controls somebody reaches for one-handed when something has gone wrong.
//
// A button is the platform's own and carries its own height; a `pressable` is
// a container this repository draws, so its target is whatever the class list
// says. That is the one this rule reads.
//
// **It asks for a minimum rather than a height.** A row holding two lines is
// already past 48 and setting a fixed height would shrink it; `min-h-` says
// *at least this*, which is what a target is.
//
// The number is not checked here and is checked next door: the class list is a
// vocabulary the edge parses, and `min-h-12` is 48 on the spacing scale it
// holds. What this can see is whether a tappable element declared one at all.

/** The class the edge reads as a floor under a target's height. */
const A_TARGET_IS_AT_LEAST = 'min-h-';

/** Every element a finger operates, which is the one this repository draws. */
const OPERATED_BY_A_FINGER = 'pressable';

/**
 * Every tappable element a template draws, as the tag it is written as.
 *
 * Read off the composed source, so a screen that moved its rows into a
 * component is a screen this still reads — six rules in this suite have gone
 * quiet that way.
 *
 * @return list<string>
 */
function everyTargetIn(Template $template): array
{
    preg_match_all(
        sprintf('/<native:%s\b.*?>/s', OPERATED_BY_A_FINGER),
        $template->composedSource(),
        $found,
    );

    return $found[0];
}

it('G3-R16 — every tappable element declares a target a finger can hit', function (): void {
    $small = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        foreach (everyTargetIn($template) as $tag) {
            $looked++;

            if (! str_contains($tag, A_TARGET_IS_AT_LEAST)) {
                $small[] = sprintf('%s — %s', $template->path, trim(preg_replace('/\s+/', ' ', $tag) ?? ''));
            }
        }
    }

    expect($looked)->toBeGreaterThan(2, 'no tappable element was found, so this rule read nothing');

    expect($small)->toBe([], sprintf(
        "These are tapped and say nothing about how big they are:\n  %s\n\n"
        . 'Give each a `min-h-` the edge can read — `min-h-12` is 48, which clears both platform '
        . "minima. Enlarge the target around the words rather than making the words bigger.\n",
        implode("\n  ", $small),
    ));
});
