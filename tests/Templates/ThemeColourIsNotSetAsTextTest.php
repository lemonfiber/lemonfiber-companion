<?php

declare(strict_types=1);

use Modules\Design\Api\ThemeToken;
use Tests\Support\Template;

// DES-R15 — the accent is a fill, and never a sentence.
//
// The resolver is handed a bare token name and cannot tell which prefix asked
// it, so `bg-theme-accent` and `text-theme-accent` both resolve to the same
// hex. One of them is the brand and the other is `lemon` on the platform's own
// light surface, which measures 1.6:1 — legible on nothing, and legible least
// of all to the reader this application is most careful about.
//
// This is the same measured finding that makes amber text-forbidden twice over
// in the brand rules, and it needs a check here for the same reason every rule
// in tests/Templates needs one: nothing else in the toolchain reads Blade, and
// the failure is a screen that renders and says nothing.
//
// Which tokens may be text is the design module's own answer, read from the
// enum rather than listed here. A token added there is covered on the same day.

$templates = Template::all();

foreach ($templates as $template) {
    it(sprintf('DES-R15 — %s sets no unreadable theme colour as text', $template->path), function () use ($template): void {
        $offenders = [];

        foreach ($template->classStrings() as $classString) {
            $classes = preg_split('/\s+/', trim($classString), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($classes === false ? [] : $classes as $class) {
                $token = themeTokenSetAsText($class);

                if ($token instanceof ThemeToken && ! $token->safeAsText()) {
                    $offenders[] = $class;
                }
            }
        }

        sort($offenders);

        expect(array_values(array_unique($offenders)))->toBe([], sprintf(
            "These set a colour as text that cannot be read as text:\n  %s\n\n"
            . 'The accent is `lemon`, which measures 1.6:1 against the platform\'s light '
            . 'surface — the same measured reason amber is never text. It is a fill, a '
            . "bar, a selected state.\nA label sitting on an accent fill is the one text "
            . 'use there is, and it is `text-theme-on-accent`, which is the foreground '
            . 'the brand pairs with lemon at 10.9:1 (DES-R15, DES-R18).',
            implode("\n  ", array_values(array_unique($offenders))),
        ));
    });
}

/**
 * The theme token a class sets as text, or null where it sets none.
 *
 * A variant prefix — `dark:`, `ios:`, `hover:` — sits in front of the utility
 * and is not part of it, so it is stripped before the prefix is read. A token
 * that is not one the design module asserts answers null: EdgeVocabularyTest
 * already reports that class as one the parser drops, and reporting it twice
 * under a rule about contrast would name the wrong cause.
 */
function themeTokenSetAsText(string $class): ?ThemeToken
{
    $tail = strrchr($class, ':');
    $utility = $tail === false ? $class : substr($tail, 1);

    if (! str_starts_with($utility, 'text-theme-')) {
        return null;
    }

    return ThemeToken::tryFrom(substr($utility, strlen('text-theme-')));
}
