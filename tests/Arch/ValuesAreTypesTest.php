<?php

declare(strict_types=1);

use Tests\Support\Vocabulary;

// D4, over the text of every module source file.
//
// The `arch()` expectation beside this rule reads *names* — it refuses a class
// called `RepairStatus` or `ConnectionState`, which is the shape a closed set
// takes when somebody writes it as a class. That is half the rule, and it is
// the half that announces itself.
//
// The other half does not. A closed set written as a literal has no name to
// refuse: `$this->scheme === 'https'` declares a two-valued vocabulary, decides
// which value means what, and does all of it inside a method body where nothing
// in this repository was looking. It was found by a reader in review, not by
// the rule that already claimed to forbid it — and a rule whose stated scope is
// wider than its mechanism is worse than a narrower rule honestly described,
// because the gap is exactly where nobody looks.

it('D4 — a closed set is an enum, and not a literal compared against', function (): void {
    $offenders = Vocabulary::comparedAgainst();

    sort($offenders);

    expect($offenders)->toBe([], sprintf(
        "These compare a value against a string literal:\n  %s\n\n"
        . 'A literal beside `===` is a vocabulary with no type, no name and no second '
        . "reader — and the second reader always arrives.\nEvery adapter in this "
        . 'repository already does the other thing: `Overall::tryFrom($said) ?? throw '
        . 'ReportIsUnreadable::overall($said)`. Declare the set as an enum in '
        . '`Modules\Kernel\Api`, put the decision on the enum so it is answered once, '
        . "and convert at the boundary (D4).\nIf the string genuinely is not a set — a "
        . 'sentinel with one member and no sibling it could grow — it is still a '
        . 'value, and a `private const` on the class it belongs to says so where a '
        . 'literal does not.',
        implode("\n  ", $offenders),
    ));
});
