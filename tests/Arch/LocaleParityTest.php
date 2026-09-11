<?php

declare(strict_types=1);

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
 * @return array<string, array<string, string>> locale => key => value
 */
function translations(): array
{
    $found = [];

    $directories = glob(Tree::at('lang/*'), GLOB_ONLYDIR);

    foreach ($directories === false ? [] : $directories as $directory) {
        $locale = basename($directory);
        $found[$locale] = [];

        foreach (Tree::filesUnder($directory, '.php') as $file) {
            /** @var mixed $group */
            $group = require $file;

            $found[$locale] = [
                ...$found[$locale],
                ...flattenKeys(basename($file, '.php'), $group),
            ];
        }
    }

    return $found;
}

/**
 * @return array<string, string>
 */
function flattenKeys(string $prefix, mixed $value): array
{
    if (is_string($value)) {
        return [$prefix => $value];
    }

    if (! is_array($value)) {
        return [];
    }

    $found = [];

    foreach ($value as $key => $nested) {
        $found = [...$found, ...flattenKeys(sprintf('%s.%s', $prefix, $key), $nested)];
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
