<?php

declare(strict_types=1);

use Tests\Support\Rules;
use Tests\Support\TestTitles;

// A test's title says what the test shows. Which rule ARCHITECTURE.md indexes it
// keeps is carried by a comment directly above it, which is where
// `TheRulesAreRealTest` reads the identifier; written into the title, it is a
// code a reader of the run has to look up before the sentence means anything.
//
// The identifiers looked for are the ones the document declares, read as
// tokens, so a title naming a version, a port or a model number is not taken
// for a rule.

/**
 * Every rule identifier one title names.
 *
 * @param list<string> $rules
 *
 * @return list<string>
 */
function theRulesOneTitleNames(string $title, array $rules): array
{
    return array_values(array_filter(
        $rules,
        static fn(string $rule): bool => preg_match(sprintf('/(?<![-A-Za-z0-9])%s(?![-0-9A-Za-z])/', preg_quote($rule, '/')), $title) === 1,
    ));
}

/**
 * Every test title that names a rule, against where it is written.
 *
 * @return list<string>
 */
function everyTitleNamingARule(): array
{
    $rules = array_keys(Rules::documented());
    $found = [];

    foreach (TestTitles::everyOne() as $where => $title) {
        $named = theRulesOneTitleNames($title, $rules);

        if ($named !== []) {
            $found[] = sprintf('%s names %s: %s', $where, implode(', ', $named), $title);
        }
    }

    return $found;
}

// G14 — no test's title names a rule
it('no test title names a rule of the architecture', function (): void {
    expect(TestTitles::everyOne())->not->toBe([], 'no test title was read, so this rule proved nothing');

    expect(everyTitleNamingARule())->toBe([], sprintf(
        "These test titles name a rule:\n  %s\n\n"
        . 'Say what the test shows in words, and put the rule\'s identifier in a comment directly '
        . 'above the test, where the rule register reads it (G14).',
        implode("\n  ", everyTitleNamingARule()),
    ));
});

it('reads a rule identifier as a token, so a longer one or a hyphenated one is not it', function (): void {
    // A matcher that answered yes to everything would satisfy the rule above
    // while reading nothing, and one that read text would take `A10` for `A1`.
    expect(theRulesOneTitleNames('A1 — no Eloquent', ['A1', 'A10']))->toBe(['A1'])
        ->and(theRulesOneTitleNames('A10 — a table has one owner', ['A1', 'A10']))->toBe(['A10'])
        ->and(theRulesOneTitleNames('answers what N2-R7 asks', ['R7', 'N2']))->toBe([])
        ->and(theRulesOneTitleNames('no table has two owners', ['A1', 'A10']))->toBe([]);
});
