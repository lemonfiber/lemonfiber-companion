<?php

declare(strict_types=1);

use Native\Mobile\Edge\TailwindParser;
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
// says. That is the one this rule reads, through the edge's own parser.
//
// **It asks for a minimum rather than a height.** A row holding two lines is
// already past 48 and setting a fixed height would shrink it; `min-h-` says
// *at least this*, which is what a target is.

/** The larger of the two platforms' minimum targets, in points: Android's 48 over iOS's 44. */
const A_FINGER_NEEDS = 48;

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

/**
 * What the edge draws a target's class list as.
 *
 * @return array<mixed>
 */
function whatTheEdgeDraws(string $tag): array
{
    return preg_match('/\sclass="([^"]*)"/', $tag, $class) === 1 ? TailwindParser::parse($class[1]) : [];
}

/** A measure the edge drew, in points, or nothing where it drew none a finger could use. */
function inPoints(mixed $measure): float
{
    if (is_int($measure) || is_float($measure)) {
        return (float) $measure;
    }

    return 0.0;
}

/** Whether a finger can hit a target the edge draws so. */
function aFingerCanHit(string $tag): bool
{
    $drawn = whatTheEdgeDraws($tag);

    return inPoints($drawn['minHeight'] ?? null) >= A_FINGER_NEEDS
        && inPoints($drawn['width'] ?? A_FINGER_NEEDS) >= A_FINGER_NEEDS;
}

it('every tappable element is drawn at least as tall and as wide as a finger needs', function (): void {
    $small = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        foreach (everyTargetIn($template) as $tag) {
            $looked++;

            if (! aFingerCanHit($tag)) {
                $small[] = sprintf('%s — %s', $template->path, trim(preg_replace('/\s+/', ' ', $tag) ?? ''));
            }
        }
    }

    expect($looked)->toBeGreaterThan(2, 'no tappable element was found, so this rule read nothing');

    expect($small)->toBe([], sprintf(
        "These are tapped and drawn smaller than a finger needs:\n  %s\n\n"
        . 'Give each a `min-h-` of at least 48 the edge can read — `min-h-12` is 48, which clears both '
        . 'platform minima — and no fixed width under it. Enlarge the target around the words rather '
        . "than making the words bigger.\n",
        implode("\n  ", $small),
    ));
});

it('reads a target too short, too narrow or declaring no floor as one a finger cannot hit', function (string $tag): void {
    expect(aFingerCanHit($tag))->toBeFalse();
})->with([
    'no floor' => ['<native:pressable class="w-full py-2">'],
    'a floor too low' => ['<native:pressable class="w-full min-h-10">'],
    'too narrow' => ['<native:pressable class="w-10 min-h-12">'],
    'no class at all' => ['<native:pressable a11y-label="Open">'],
]);

it('reads a target at a finger\'s size or past it as one a finger can hit', function (string $tag): void {
    expect(aFingerCanHit($tag))->toBeTrue();
})->with([
    'a full-width row' => ['<native:pressable class="w-full min-h-12 py-2">'],
    'a fixed width past it' => ['<native:pressable class="w-12 min-h-12">'],
    'a taller floor' => ['<native:pressable class="w-28 h-16 min-h-16">'],
]);
