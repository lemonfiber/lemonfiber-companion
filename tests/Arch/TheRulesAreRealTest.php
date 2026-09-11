<?php

declare(strict_types=1);

use function Tests\Support\documentedRules;
use function Tests\Support\enforcementSources;

// ARCHITECTURE.md, checked against itself.
//
// The document carries a table of rules and, for each, the mechanism that
// enforces it. Nothing verified that column, and it was wrong twice: D4
// ("enums for every closed set") claimed `arch` and had no rule behind it, and
// A6 named `not->toHaveStaticProperties()`, a Pest expectation that does not
// exist — the test had been rewritten to use reflection and the document was
// left describing the mechanism that never worked.
//
// Both survived review because a rule table is exactly the kind of document
// people stop reading once they trust it. A table that can lie is worse than no
// table, so this makes lying fail.
//
// Every enforcement artifact carries its rule's identifier: arch rules in their
// description, PHPStan bans in the `message:` a developer reads when blocked,
// configuration in a comment above the setting. The identifier is the join.

it('enforces every rule the architecture documents', function (): void {
    $unenforced = [];

    foreach (documentedRules() as $id => $claim) {
        // A rule that genuinely cannot be mechanised says so, in that word. It
        // is not a loophole — it is the honest answer, and the count below
        // makes how many there are visible rather than buried in prose.
        if ($claim === 'review' || $claim === 'planned') {
            continue;
        }

        if (! str_contains(enforcementSources(), $id)) {
            $unenforced[] = sprintf('%s (claims "%s")', $id, $claim);
        }
    }

    expect($unenforced)->toBe([], sprintf(
        "ARCHITECTURE.md claims these are enforced and nothing carries their identifier:\n  %s\n\n"
        . 'Either write the rule and tag it with its identifier, or change the '
        . "enforcement column to `review` and accept that a human has to catch it.",
        implode("\n  ", $unenforced),
    ));
});

it('documents every rule the codebase enforces', function (): void {
    $documented = array_keys(documentedRules());
    $undocumented = [];

    // The other direction. A rule enforced but absent from the table is how the
    // document goes stale from the far end: the codebase gets stricter, the
    // document stops describing it, and the next reader trusts the document.
    preg_match_all('/(?<![-A-Za-z0-9])([A-Z]\d{1,2})\b(?=\s*[—\/,)])/u', enforcementSources(), $found);

    foreach (array_unique($found[1]) as $id) {
        if (! in_array($id, $documented, true)) {
            $undocumented[] = $id;
        }
    }

    sort($undocumented);

    expect($undocumented)->toBe([], sprintf(
        "These identifiers are enforced somewhere but appear in no ARCHITECTURE.md rule table:\n  %s",
        implode(', ', $undocumented),
    ));
});

it('does not let the unbuilt rules grow', function (): void {
    // A ratchet, not a budget. `planned` is honest about a rule that is agreed
    // and not yet written, but nothing stops it becoming the place rules go to
    // be forgotten — so the count may fall and may not rise. Lower the ceiling
    // when you lower the count.
    $ceiling = 6;

    $planned = array_keys(array_filter(
        documentedRules(),
        static fn(string $claim): bool => $claim === 'planned',
    ));

    sort($planned);

    expect(count($planned))->toBeLessThanOrEqual($ceiling, sprintf(
        "%d rules are documented as `planned`, and the ceiling is %d: %s.\n"
        . 'A new rule is either enforced and tagged, or it is not documented yet.',
        count($planned),
        $ceiling,
        implode(', ', $planned),
    ));
});

it('reports how many rules rest on a human reading the diff', function (): void {
    $review = array_keys(array_filter(
        documentedRules(),
        static fn(string $claim): bool => $claim === 'review',
    ));

    sort($review);

    // Not a failure — a number worth seeing. Every rule here is one the suite
    // cannot catch, so it is the standing cost of the architecture, and it
    // should be argued down rather than allowed to drift up quietly.
    expect($review)->toBeArray();

    fwrite(STDOUT, sprintf(
        "\n  rules resting on review: %d%s\n",
        count($review),
        $review === [] ? '' : ' (' . implode(', ', $review) . ')',
    ));
});
