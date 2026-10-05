<?php

declare(strict_types=1);

use Modules\Design\Api\Typeface;
use Tests\Support\Template;

// F20 — every line of text names the bundled face it is set in.
//
// A text element naming no face is drawn in the app's interface face, which
// is right for running text and wrong for an address or a log line. Naming the
// face on every element makes that choice where it can be read, and lets this
// check that the face is one the app bundles: a name the bundle does not have
// falls back to the platform's own face on the glass and to nothing anywhere a
// rule could see.
//
// The weight is checked with it. Each face is one weight, and the class beside
// it must ask for that weight: iOS applies the class's weight to the face, so a
// class that disagrees draws a weight nobody chose, and Android synthesises a
// bold the face does not have.

/**
 * The weight class each face is drawn with, an empty string for the regular weight.
 *
 * Written here rather than derived, so the rule has something of its own to
 * compare against.
 *
 * @return array<string, string> face => weight class
 */
function theWeightEachFaceIs(): array
{
    return [
        Typeface::Interface->value => '',
        Typeface::InterfaceMedium->value => 'font-medium',
        Typeface::InterfaceSemiBold->value => 'font-semibold',
        Typeface::InterfaceBold->value => 'font-bold',
        Typeface::Figures->value => '',
        Typeface::FiguresMedium->value => 'font-medium',
    ];
}

/** The weight classes a text element's class list may hold. */
const THE_WEIGHT_CLASSES = ['font-thin', 'font-extralight', 'font-light', 'font-normal', 'font-medium', 'font-semibold', 'font-bold', 'font-extrabold', 'font-black'];

/** The classes that pick a platform face over the bundled ones. */
const THE_PLATFORM_FACES = ['font-sans', 'font-serif', 'font-mono'];

/**
 * The value of one attribute written out on an element, or null where it is not.
 *
 * Named for this file: the root suites share one namespace (G10).
 */
function theAttributeWritten(string $attributes, string $name): ?string
{
    return preg_match(sprintf('/(?<![:\w-])%s\s*=\s*"([^"]*)"/', $name), $attributes, $found) === 1 ? $found[1] : null;
}

/**
 * The classes in a list, one per entry.
 *
 * @return list<string>
 */
function theClassesAFaceSitsWith(string $classes): array
{
    $split = preg_split('/\s+/', trim($classes));

    return array_values(array_filter(is_array($split) ? $split : [], static fn(string $class): bool => $class !== ''));
}

/**
 * The weight classes a face is drawn with: none for the regular weight.
 *
 * @return list<string>
 */
function theWeightClassesOf(string $face): array
{
    $weight = theWeightEachFaceIs()[$face] ?? '';

    return $weight === '' ? [] : [$weight];
}

/** What is wrong with one text element's face, or nothing. */
function whatIsWrongWithItsFace(string $attributes): ?string
{
    $face = theAttributeWritten($attributes, 'font');

    if ($face === null) {
        return 'names no face';
    }

    if (Typeface::tryFrom($face) === null) {
        return sprintf('names `%s`, which is not a bundled face', $face);
    }

    $weights = array_values(array_intersect(theClassesAFaceSitsWith(theAttributeWritten($attributes, 'class') ?? ''), THE_WEIGHT_CLASSES));
    $expected = theWeightClassesOf($face);

    if ($weights !== $expected) {
        return sprintf('sets `%s` with [%s], which is drawn with [%s]', $face, implode(' ', $weights), implode(' ', $expected));
    }

    return null;
}

/**
 * Every text element whose face is wrong, as `path:line what`.
 *
 * @return list<string>
 */
function textInAFaceNotBundled(): array
{
    $wrong = [];

    foreach (Template::all() as $template) {
        foreach ($template->elements() as $element) {
            $why = $element['tag'] === 'text' ? whatIsWrongWithItsFace($element['attributes']) : null;

            if ($why !== null) {
                $wrong[] = sprintf('%s:%d %s', $template->path, $element['line'], $why);
            }
        }
    }

    sort($wrong);

    return $wrong;
}

it('knows the weight of every face the app bundles', function (): void {
    expect(array_keys(theWeightEachFaceIs()))->toEqualCanonicalizing(array_map(
        static fn(Typeface $face): string => $face->value,
        Typeface::cases(),
    ));
});

it('every text element names a bundled face, with the weight class that face is', function (): void {
    expect(textInAFaceNotBundled())->toBe([], sprintf(
        "These text elements are not set in a face the app bundles at its own weight:\n  %s\n\n"
        . 'Draw text through a design element (`x-design::body`, `x-design::verbatim` and the rest), '
        . 'each of which names its face. Interface text is set in Golos Text and figures, '
        . 'identifiers, timestamps and log text in DM Mono (DES-R32).',
        implode("\n  ", textInAFaceNotBundled()),
    ));
});

it('picks no platform face over a bundled one', function (): void {
    $platform = [];

    foreach (Template::all() as $template) {
        foreach ($template->classStrings() as $classes) {
            foreach (array_intersect(theClassesAFaceSitsWith($classes), THE_PLATFORM_FACES) as $class) {
                $platform[] = sprintf('%s uses `%s`', $template->path, $class);
            }
        }
    }

    expect($platform)->toBe([]);
});

it('refuses a face it does not bundle and a weight its face is not', function (): void {
    expect(whatIsWrongWithItsFace('class="text-theme-text"'))->toBe('names no face')
        ->and(whatIsWrongWithItsFace('class="text-theme-text" font="Inter-Regular"'))->toContain('not a bundled face')
        ->and(whatIsWrongWithItsFace('class="font-bold text-theme-text" font="GolosText-Regular"'))->toContain('drawn with []')
        ->and(whatIsWrongWithItsFace('class="text-theme-text" font="GolosText-Bold"'))->toContain('drawn with [font-bold]')
        ->and(whatIsWrongWithItsFace('class="font-medium text-theme-text" font="DMMono-Medium"'))->toBeNull();
});
