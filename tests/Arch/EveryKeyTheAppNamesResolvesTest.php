<?php

declare(strict_types=1);

use Tests\Support\Catalogue;
use Tests\Support\Tree;

// L7 — a key the application names is a key the catalogue holds.
//
// `L2` compares the locales against each other, which catches a key that is in
// one and not the other. What neither it nor anything else can see is a key
// that is in *neither* — a typo at the call site, a key renamed in the
// catalogue and not at the reader, a sentence deleted while the line that shows
// it stayed.
//
// The failure is the same one `L2` is written about and it is worse, because it
// survives a locale sweep: Laravel looks the key up, misses, falls back, misses
// again, and renders the key. `notifications.plain.titel` appears on somebody's
// phone as those words. Nothing in the type system, the analyser or the suite
// sees a string.
//
// Read over the text of the sources rather than by resolving anything at
// runtime, because the point is the keys nothing ever reaches: a branch that
// only runs on a handset is exactly where a mistyped key survives longest, and
// a rule that had to execute the line would never see it.
//
// **The cure is usually not a corrected literal.** Where a key is derived from
// a closed set — `Permission::reason()` builds `device.camera_reason` from the
// case — there is one spelling and this rule is proving it about the string the
// application actually uses. A literal is the shape that drifts.

/** Where a person's words are read from, in production code and in templates. */
const READS_A_KEY = [
    '/\bwords->for\(\s*\'([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+)\'/',
    '/\b__\(\s*\'([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+)\'/',
    '/\btrans\(\s*\'([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+)\'/',
];

/**
 * Every catalogue key named in production code, with the file that names it.
 *
 * Templates are read too, and they are the half most likely to carry one: a
 * Blade file is not PHP any analyser reads, so a mistyped key there is
 * invisible to everything except this.
 *
 * @return array<string, list<string>> key => the files naming it
 */
function keysNamedInSource(): array
{
    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap/Composition'), '.php'),
        ...Tree::filesUnder(Tree::at('native/src'), '.php'),
    ];

    $found = [];

    foreach ($sources as $file) {
        // A module's own `tests/` is not production code, and a fixture naming
        // a key that does not exist is a fixture rather than a defect.
        if (str_contains($file, '/tests/')) {
            continue;
        }

        $source = file_get_contents($file);

        if (! is_string($source)) {
            continue;
        }

        $where = str_replace(sprintf('%s/', Tree::root()), '', $file);

        foreach (READS_A_KEY as $pattern) {
            preg_match_all($pattern, $source, $named);

            foreach ($named[1] as $key) {
                $found[$key][] = $where;
            }
        }
    }

    return $found;
}

it('L7 — every key the application names is in the catalogue', function (): void {
    $named = keysNamedInSource();

    // Not a guard against an empty repository — a guard against the patterns
    // above quietly matching nothing, which is how this rule would die. It has
    // happened to two rules in this suite already.
    expect($named)->not->toBeEmpty('No catalogue key was found in any source file, so this checked nothing.');

    $missing = [];

    foreach (Catalogue::locales() as $locale) {
        $lines = Catalogue::all($locale);

        foreach ($named as $key => $files) {
            if (($lines[$key] ?? '') !== '') {
                continue;
            }

            sort($files);

            $missing[] = sprintf('%s — %s, named by %s', $key, $locale, implode(' and ', array_unique($files)));
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "These keys are read and the catalogue does not hold them:\n  %s\n\n"
        . 'Laravel looks a missing key up, falls back, misses again and renders the key '
        . "itself — so the key appears on the screen where a sentence belongs, in every\n"
        . 'locale at once, which is the one case `L2` cannot see because both catalogues '
        . "agree.\nAdd the line to every locale under `lang/<locale>/<module>.php`. Where "
        . 'the key belongs to a closed set, derive it from the case instead of writing it '
        . 'out — `Permission::reason()` is the shape, and a derived key cannot drift from '
        . 'the rule that checks it (L7, L2).',
        implode("\n  ", $missing),
    ));
});
