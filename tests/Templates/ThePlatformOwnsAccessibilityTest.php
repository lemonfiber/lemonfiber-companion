<?php

declare(strict_types=1);

use Tests\Support\Template;

// The platform's accessibility settings are honoured, not reimplemented.
//
// The requirement has two halves and the second is the one that needs a rule.
// *Honouring* the settings is mostly what happens by default: EDGE renders
// native views, and a native view scales its text, raises its contrast and
// stills its animations because the operating system told it to. Nothing has to
// be written for that to work.
//
// What breaks it is writing something. A fixed line spacing does not grow when
// somebody turns text size up, because a number is a number; an animation with a
// duration this app chose does not stop when reduced-motion is on, because
// nothing asked. Both look correct on the machine they were written on, where
// every setting is at its default — and both are invisible to every other rule
// here.
//
// So this refuses the two ways a template can take the decision away from the
// platform, and it is deliberately narrow. It cannot judge whether a screen is
// usable at 200% text; what it can do is refuse the constructs that guarantee it
// is not.

$templates = Template::all();

/**
 * Class patterns that pin a measure the platform is meant to scale.
 *
 * A text size is not among them. A named step and a bracketed one reach the
 * renderer alike, as a size in points, and both renderers scale it: iOS with
 * Dynamic Type relative to its body style, Android as `sp`. Which sizes a
 * template may set is the brand's, and `tests/Arch/BrandMeasuresParityTest.php`
 * holds every one to them. A bracketed line height or tracking is different:
 * iOS applies it as a fixed spacing, which stays put while the text grows.
 */
const PINS_A_SIZE = [
    '/\bleading-\[[^\]]+\]/',
    '/\btracking-\[[^\]]+\]/',
];

/**
 * Attributes that animate on this app's say-so rather than the platform's.
 *
 * An animation the operator asked not to see is one of the four platform
 * settings this app honours, and it is the one with no sensible default: a
 * duration written
 * into a template runs at that duration whatever the device has been told.
 */
const ANIMATES_REGARDLESS = ['animate', 'animation', 'transition', 'duration'];

// Both rules below are written per template, so an empty list of them writes
// no test at all rather than a test that passes — which is the quietest way
// for this file to stop holding anything. The second reads elements rather
// than lines, and a reader that stopped finding them would be just as silent,
// so both are counted once here before the loop.
it('there is a template to read, with elements in it', function () use ($templates): void {
    expect($templates)->not->toBe([], 'no template was found, so neither rule below was written at all');

    $elements = [];

    foreach ($templates as $template) {
        $elements = [...$elements, ...$template->elements()];
    }

    expect($elements)->not->toBe([], 'no template holds an element, so the rule about animation read nothing');
});

foreach ($templates as $template) {
    it(sprintf('nothing in %s pins a measure the platform should scale', $template->path), function () use ($template): void {
        $pinned = [];

        foreach (explode("\n", $template->source) as $at => $line) {
            foreach (PINS_A_SIZE as $pattern) {
                if (preg_match($pattern, $line, $found) === 1) {
                    $pinned[] = sprintf('%s:%d — %s', $template->path, $at + 1, $found[0]);
                }
            }
        }

        expect($pinned)->toBe([], sprintf(
            "These pin a line height or tracking the platform is meant to scale:\n  %s\n\n"
            . 'A bracketed line height or tracking is applied as a fixed spacing on iOS, '
            . 'which does not grow when somebody turns text size up — so the screen that '
            . "looks right on a developer's phone is the one an operator cannot read.\n"
            . 'Leave the line height and tracking to the platform, which scales them with '
            . 'the text (N4-R14).',
            implode("\n  ", $pinned),
        ));
    });

    it(sprintf('nothing in %s animates against the operator\'s wishes', $template->path), function () use ($template): void {
        $moving = [];

        foreach ($template->elements() as $element) {
            foreach (ANIMATES_REGARDLESS as $attribute) {
                // Matched as a whole attribute name, so `duration` is found and
                // `data-duration-note` is not. `attributes` is the raw text
                // between the tag and its close, which is what `Template`
                // hands over — parsing it into pairs here would be a second,
                // worse copy of what the renderer does.
                if (preg_match(sprintf('/(?:^|\s)%s\s*=/', preg_quote($attribute, '/')), $element['attributes']) === 1) {
                    $moving[] = $template->describe(
                        sprintf('%s %s', $element['tag'], $attribute),
                        $element['line'],
                    );
                }
            }
        }

        expect($moving)->toBe([], sprintf(
            "These animate on the app's say-so rather than the platform's:\n  %s\n\n"
            . 'Reduced-motion is one of the four settings N4-R14 names, and it is the one '
            . 'with no sensible default — a duration written into a template runs at that '
            . "duration whatever the device has been told.\n"
            . 'Leave the movement to the platform\'s own transitions, which stop when the '
            . 'operator asks them to (N4-R14).',
            implode("\n  ", $moving),
        ));
    });
}
