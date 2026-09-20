<?php

declare(strict_types=1);

use Tests\Support\Rules;

// ARCHITECTURE.md, checked against itself.
//
// The document carries a table of rules and, for each, the mechanism that
// enforces it. That column is the part a reader trusts and stops checking, and
// it can go wrong in two ways that look identical from the outside: a rule that
// claims `arch` with nothing behind it, and a rule that names a Pest
// expectation which does not exist, so the suite reports a green tick for a
// check it never ran.
//
// A table that can lie is worse than no table, so both directions fail here.
//
// Every enforcement artifact carries its rule's identifier: arch rules in their
// description, PHPStan bans in the `message:` a developer reads when blocked,
// configuration in a comment above the setting. The identifier is the join.

it('enforces every rule the architecture documents', function (): void {
    $unenforced = [];

    foreach (Rules::documented() as $id => $claim) {
        // A rule that genuinely cannot be mechanised says so, in that word. It
        // is not a loophole — it is the honest answer, and the count below
        // makes how many there are visible rather than buried in prose.
        if ($claim === 'review' || $claim === 'planned') {
            continue;
        }

        if (! str_contains(Rules::enforcementSources(), $id)) {
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

it('enforces every rule by the kind of mechanism it claims', function (): void {
    // The join above is the identifier, and an identifier appears anywhere. A
    // rule naming three mechanisms needs only one of them to mention it, so a
    // row could claim `arch` while nothing under `tests/Arch` had ever heard of
    // it — and the table would say enforced, three times over, on the strength
    // of a comment in `phpstan.neon`.
    //
    // That is not hypothetical either. `H3` read *methods per class, lines per
    // method, constructor parameters, cognitive complexity* with one mechanism
    // behind the last clause; `F1` claimed an arch size cap that did not exist;
    // and `G3` claimed an arch half beside a runtime one that turned out not to
    // cover the client this application actually uses. All three passed the
    // check above.
    //
    // So the kind is checked too: a row that says `arch` is looked for where
    // arch rules live, and one that says `phpstan` where those live. It does
    // not prove the clause count — nothing can — but it does refuse the
    // cheapest way for a row to be wider than its mechanism.
    $wrong = [];

    foreach (Rules::documented() as $id => $claim) {
        if ($claim === 'review' || $claim === 'planned') {
            continue;
        }

        foreach (Rules::whereEachKindLives() as $kind => $sources) {
            if (! Rules::claimsTheKind($claim, $kind)) {
                continue;
            }

            if (! Rules::carriesTheRule($id, $sources)) {
                $wrong[] = sprintf('%s claims "%s" and nothing under %s carries it', $id, $claim, $kind);
            }
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These name a kind of mechanism that does not carry them:\n  %s\n\n"
        . 'A row naming several mechanisms needs only one of them to mention its identifier, '
        . 'which is how a clause with nothing behind it hides in a green table. Write the '
        . "mechanism the row names, or name the one that is really there.\n",
        implode("\n  ", $wrong),
    ));
});

it('reads a claimed mechanism as a word, so a row cannot borrow one by spelling', function (): void {
    // The two confusions this cost, both of which passed as claims. Reading the
    // claim as text made *architecture* a claim to an arch rule by spelling,
    // and made a row naming the generated `@phpstan-type` line — the thing an
    // arch rule reads — a claim to a PHPStan rule. Each was then asked for its
    // identifier somewhere it had never promised to be, and a row that names
    // two mechanisms needs only one of them to answer.
    //
    // The three true cases are here beside them because the obvious fix is
    // narrower than the bug: reading only the word that opens a claim would
    // refuse *architecture* correctly and quietly stop asking the rows that
    // name their second mechanism last.
    expect(Rules::claimsTheKind('arch: over the suites that write a wire body', 'arch'))->toBeTrue()
        ->and(Rules::claimsTheKind('composer + arch', 'arch'))->toBeTrue()
        ->and(Rules::claimsTheKind('review, plus an arch check for the obvious markers', 'arch'))->toBeTrue()
        ->and(Rules::claimsTheKind('test: the architecture of a screen, compared', 'arch'))->toBeFalse()
        ->and(Rules::claimsTheKind('arch: the `@phpstan-type` line, read off the envelope', 'phpstan'))->toBeFalse();
});

it('documents every rule the codebase enforces', function (): void {
    $documented = array_keys(Rules::documented());
    $undocumented = [];

    // The other direction. A rule enforced but absent from the table is how the
    // document goes stale from the far end: the codebase gets stricter, the
    // document stops describing it, and the next reader trusts the document.
    preg_match_all('/(?<![-A-Za-z0-9])([A-Z]\d{1,2})\b(?=\s*[—\/,)])/u', Rules::enforcementSources(), $found);

    foreach (array_unique($found[1]) as $id) {
        if (! in_array($id, $documented, strict: true)) {
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
    $ceiling = 0;

    $planned = array_keys(array_filter(
        Rules::documented(),
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
        Rules::documented(),
        static fn(string $claim): bool => $claim === 'review',
    ));

    sort($review);

    // Not a failure — a number worth seeing. Every rule here is one the suite
    // cannot catch, so it is the standing cost of the architecture, and it
    // should be argued down rather than allowed to drift up quietly.
    //
    // What is asserted is that there was a table to read. A reporter needs an
    // expectation or Pest marks it risky, and the cheap one to reach for —
    // *this list is a list* — is an expectation no reading can fail. With one
    // of those here, a table that stops parsing prints nothing resting on
    // review, and nothing resting on review reads exactly like a cost that has
    // been argued away.
    expect(Rules::documented())->not->toBe([], 'no rules were read out of the architecture, so this counted nothing');

    fwrite(STDOUT, sprintf(
        "\n  rules resting on review: %d%s\n",
        count($review),
        $review === [] ? '' : sprintf(' (%s)', implode(', ', $review)),
    ));
});
