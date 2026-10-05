<?php

declare(strict_types=1);

use Tests\Support\Catalogue;

// L8 — a tab, a menu item and a menu group name what they open in a word or a
// few, never a sentence.
//
// Read from the navigation catalogue in every language, because a label that
// fits in English and runs to a sentence in Dutch is the same fault. At most
// three words, and no closing full stop, which is how a sentence gives itself
// away.

const AT_MOST_THIS_MANY_WORDS = 3;

// L8 — a tab, a menu item and a menu group name what they open in at most three words, and never as a sentence
it('every navigation label is at most three words and no sentence, in every language', function (): void {
    $long = [];
    $read = 0;

    foreach (Catalogue::locales() as $locale) {
        foreach (Catalogue::of($locale, 'navigation') as $key => $said) {
            $read++;
            $words = preg_split('/\s+/u', trim($said), flags: PREG_SPLIT_NO_EMPTY);

            if (count($words === false ? [] : $words) > AT_MOST_THIS_MANY_WORDS || str_ends_with($said, '.')) {
                $long[] = sprintf('%s (%s): %s', $key, $locale, $said);
            }
        }
    }

    expect($read)->toBeGreaterThan(0, 'the navigation catalogue holds no line, so this read nothing')
        ->and($long)->toBe([], sprintf(
            "These navigation labels are longer than three words, or end like a sentence:\n  %s\n",
            implode("\n  ", $long),
        ));
});
