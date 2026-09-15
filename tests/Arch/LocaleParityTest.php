<?php

declare(strict_types=1);

use Tests\Support\Catalogue;
use Tests\Support\Tree;

// L2 — every locale carries the same keys, and every value is a sentence.
//
// A missing key fails nothing on its own. Laravel looks the key up, does not
// find it, falls back, does not find it there either, and renders the key —
// so a Dutch device shows `health.unreachable` where a sentence belongs, and
// ships that way. Nothing in the type system, the analyser or the browser can
// see it; the only thing that can is a comparison of the two files.
//
// Both directions, because a key in `nl` with no `en` counterpart is a typo or
// dead weight, and both cost the same to find here and are invisible later.
//
// Written over whatever directories exist under `lang/` rather than over a list
// of locales, so adding one is a matter of adding the directory.

/**
 * Every translation key, flattened to dotted form, per locale.
 *
 * Read through `Catalogue` rather than walked here: three rules needed the same
 * flatten and the same knowledge of where `lang/` is, and three copies of that
 * disagree the day one of them moves — quietly, because the copy that stops
 * finding files still passes.
 *
 * @return array<string, array<string, string>> locale => key => value
 */
function translations(): array
{
    $found = [];

    foreach (Catalogue::locales() as $locale) {
        $found[$locale] = Catalogue::all($locale);
    }

    return $found;
}

it('L2 — the locales carry the same keys', function (): void {
    $locales = translations();

    expect($locales)->not->toBeEmpty('No locale directory was read, so this compares nothing.');
    expect(array_keys($locales))->toContain('en');

    $missing = [];

    foreach ($locales as $locale => $keys) {
        foreach ($locales as $other => $otherKeys) {
            if ($locale === $other) {
                continue;
            }

            foreach (array_diff(array_keys($keys), array_keys($otherKeys)) as $key) {
                $missing[] = sprintf('%s has %s and %s does not', $locale, $key, $other);
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([], sprintf(
        "The locales have drifted apart:\n  %s\n\n"
        . 'A key present in one locale and absent from another does not fail anything '
        . 'by itself: the translator renders the key, so the screen reads '
        . '`health.unreachable` where a sentence belongs and nothing says why. The '
        . 'reverse is as wrong — a key with no counterpart is either a typo or text '
        . 'nothing shows any more (L2).',
        implode("\n  ", $missing),
    ));
});

it('L2 — a locale the spell checker cannot read is excluded from it', function (): void {
    $configuration = file_get_contents(Tree::at('typos.toml'));

    // Read out of the `extend-exclude` list rather than looked for anywhere in
    // the file, so a locale named in a comment does not satisfy this.
    $excluded = preg_match('/^extend-exclude\s*=\s*\[(.*?)\]/ms', is_string($configuration) ? $configuration : '', $found) === 1
        ? $found[1]
        : '';

    $unlisted = [];

    foreach (array_keys(translations()) as $locale) {
        // English is the checker's own language, so a typo there is a typo an
        // operator would see and is worth catching.
        if ($locale === 'en') {
            continue;
        }

        if (! str_contains($excluded, sprintf('lang/%s/', $locale))) {
            $unlisted[] = $locale;
        }
    }

    expect($unlisted)->toBe([], sprintf(
        "These catalogues are read by a spell checker that does not speak them:\n  %s\n\n"
        . 'The checker\'s dictionary is English, so every sentence in another language is '
        . 'a run of words it does not have and the hygiene gate fails on all of them at '
        . 'once. Add `lang/<locale>/**` to `extend-exclude` in typos.toml. This fails here '
        . 'rather than in CI because the CI failure names forty Dutch words and not the '
        . 'one missing line (L2).',
        implode("\n  ", $unlisted),
    ));
});

it('L2 — no translation is a placeholder for one', function (): void {
    $offenders = [];

    foreach (translations() as $locale => $keys) {
        foreach ($keys as $key => $value) {
            if (trim($value) === '') {
                $offenders[] = sprintf('%s/%s is empty', $locale, $key);
            }

            if ($value === $key) {
                $offenders[] = sprintf('%s/%s is its own key', $locale, $key);
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These are the shape a half-finished translation has:\n  %s\n\n"
        . 'An empty value renders as nothing, and a value equal to its key renders as '
        . 'the key — which is exactly what a missing key looks like, so both defeat the '
        . 'parity check above by satisfying it. Write the sentence or remove the key '
        . '(L2).',
        implode("\n  ", $offenders),
    ));
});

/**
 * The placeholders a line names, lowercased and deduplicated.
 *
 * Lowercased because `:name`, `:Name` and `:NAME` are one variable — the casing
 * chooses how the replacement is capitalised, not which value arrives. A rule
 * that read them as three would demand a translator match the English
 * capitalisation of a word their own language does not capitalise.
 *
 * `(?<!\w)` so a time or a scheme is not a placeholder: `18:30` cannot match
 * because a placeholder starts with a letter, and `https://` cannot because a
 * slash is not one.
 *
 * @return list<string>
 */
function thePlaceholdersIn(string $line): array
{
    preg_match_all('/(?<!\w):([A-Za-z_][A-Za-z0-9_]*)/', $line, $found);

    $named = array_map(strtolower(...), $found[1]);
    sort($named);

    return array_values(array_unique($named));
}

it('L2 — every locale names the same placeholders in a line', function (): void {
    // The gap the parity check above leaves open, and it is not a small one: a
    // key present in both locales with a sentence in each satisfies every rule
    // in this file, and puts the placeholder itself on the glass the day a
    // translator names the variable in their own language rather than leaving
    // it alone. Nothing else in the toolchain reads a catalogue value for what
    // it interpolates.
    //
    // Against the first locale rather than pairwise, because the first is where
    // a key is written and the rest are translations of it — and a pairwise
    // check would report the same disagreement once per pair.
    $locales = Catalogue::locales();
    $first = $locales[0];
    $source = translations()[$first] ?? [];
    $offenders = [];

    foreach ($locales as $locale) {
        if ($locale === $first) {
            continue;
        }

        foreach (translations()[$locale] ?? [] as $key => $value) {
            $wanted = thePlaceholdersIn($source[$key] ?? '');
            $named = thePlaceholdersIn($value);

            if ($wanted !== $named) {
                $offenders[] = sprintf(
                    '%s/%s names %s where %s names %s',
                    $locale,
                    $key,
                    $named === [] ? 'none' : implode(', ', $named),
                    $first,
                    $wanted === [] ? 'none' : implode(', ', $wanted),
                );
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These lines interpolate different things in different languages:\n  %s\n\n"
        . 'A placeholder the caller does not pass is rendered as itself, so the operator '
        . 'is shown the name of the variable rather than a number — and one the caller '
        . "does pass and the line does not name is silently dropped.\nName the same "
        . 'variables as the source locale; translate the sentence around them, not the '
        . 'variable (L2).',
        implode("\n  ", $offenders),
    ));
});
