<?php

declare(strict_types=1);

use Tests\Support\Imports;
use Tests\Support\Module;
use Tests\Support\Tree;

// W6 — a source file declares one class, and it is the one its path names.
//
// Every rule on this page is derived from a path or a namespace, and the join
// between the two is made in one place: `Module::classNames()` turns a file
// path into the class name PSR-4 would give it, asks `Imports::declaredName()`
// what the file actually declares, and keeps the file when they agree. That
// list is what the arch rules iterate.
//
// `declaredName()` reads the *first* class in the file. So a file holding two
// of them answers for the first and says nothing about the second, and the
// second is not reported as wrong — it is not a candidate at all. Nothing
// refuses it, because nothing is looking at it.
//
// **This is a different failure from the one the floor cures, and the difference
// decides the fix.** L1 narrowed to two directories that did not exist, F2 to a
// namespace with no classes in it, F5 to five tag names no screen used. In all
// three the selected set was wrong, so asserting the set is not empty catches
// them. Here the set is right: discovery returns exactly what it was asked for,
// and the class it cannot see was never a candidate to be counted. A floor
// counts correctly and still sees nothing. The question that catches this one
// is not *did the rule select anything* but *is there anything the rule could
// not have selected*.
//
// Proved rather than argued. A second class in a source file, deliberately not
// final, not readonly, and holding mutable public state, is refused by nothing:
// not `every class is final`, not the readonly rule, not `H1`'s banned names,
// not `D4`, not `F2`.
//
// **The autoload hazard is the concrete half.** A second class is reachable
// only because something else pulled its sibling in first. PSR-4 maps
// `Modules\Operator\Internal\WhatTheCoreAddedUnderneath` to
// `Internal/WhatTheCoreAddedUnderneath.php`, which does not exist — so the day
// a file names that class before it names the one it shares a file with, the
// autoloader has nowhere to look and the app fatals. It works today by the
// order two references happen to appear in.
//
// **Both halves, because either alone leaves the other free.** One class under
// the wrong name is a PSR-4 miss that loads only by accident; two classes under
// a correct first name is the case above. A rule asserting only the count would
// pass a file whose single class is misnamed, and one asserting only the name
// would pass a file whose first class is right and whose second is anything.
//
// **Source only, and tests deliberately not.** Twenty-five Pest files declare a
// small carrier for an `either()` arm beside the test that uses it — a shape
// that is right there and wrong in `src/`. Those files are loaded by PHPUnit
// directly rather than by the autoloader, so the hazard above cannot reach
// them, and no rule maps their path to a class name: `Module::classNames()`
// reads `src/`, and `H4` governs where a test sits by path alone.

/**
 * Every file the class-name rules read a class out of.
 *
 * @return list<string>
 */
function everySourceFile(): array
{
    $found = [];

    foreach (Module::all() as $module) {
        $found = [...$found, ...$module->classes()];
    }

    return [
        ...$found,
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
        ...Tree::filesUnder(Tree::at('phpstan'), '.php'),
        ...Tree::filesUnder(Tree::at('bridge/src'), '.php'),
    ];
}

/**
 * What is wrong with one file's declarations, or the empty string where nothing is.
 *
 * Split out from the walk so that the judgement can be handed the cases it must
 * refuse. A rule that only ever sees a clean tree is a rule nobody has watched
 * say no, which is the shape this whole file is about.
 *
 * @param list<string> $declared
 */
function whatIsWrongWith(string $stem, array $declared): string
{
    if (count($declared) > 1) {
        return sprintf('declares %d: %s', count($declared), implode(', ', $declared));
    }

    // A file declaring nothing is ordinary — a config fragment, a route list —
    // and is left alone. What is refused is a declaration the path does not name.
    if ($declared !== [] && $declared[0] !== $stem) {
        return sprintf('declares %s', $declared[0]);
    }

    return '';
}

it('W6 — a source file declares one class, and it is the one its path names', function (): void {
    $offenders = [];

    foreach (everySourceFile() as $file) {
        $wrong = whatIsWrongWith(basename($file, '.php'), Imports::declaredNames($file));

        if ($wrong !== '') {
            $offenders[] = sprintf('%s %s', str_replace(sprintf('%s/', Tree::root()), '', $file), $wrong);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These hold a class no rule on this page could have selected:\n  %s\n\n"
        . 'Every architecture rule reads a class out of its path, so a second class in '
        . 'a file is judged by none of them — not `every class is final`, not the '
        . 'readonly rule, not the boundary rules. It is also reachable only because '
        . 'something else loaded its sibling first: PSR-4 maps it to a file that does '
        . "not exist.\nGive it its own file, named for it (W6).",
        implode("\n  ", $offenders),
    ));
});

it('W6 — the judgement is watched refusing', function (): void {
    // The two halves, each shown saying no. Asserting only over the tree would
    // hold just as well for a judgement that says yes to everything — and a
    // clean tree is exactly what this repository has today, so there is nothing
    // in the walk above that would notice.
    expect(whatIsWrongWith('Upkeep', ['Upkeep', 'AndOneBeside']))->not->toBe('');
    expect(whatIsWrongWith('Upkeep', ['SomethingElse']))->not->toBe('');

    // And saying yes to the two shapes that are right, so the rule cannot be
    // satisfied by refusing everything either.
    expect(whatIsWrongWith('Upkeep', ['Upkeep']))->toBe('');
    expect(whatIsWrongWith('routes', []))->toBe('');
});

it('W6 — the files the rule reads are found', function (): void {
    // The guard asked for, on this rule's own selection. It is not the
    // cure for what W6 refuses — that is the whole argument above — but this
    // rule discovers a file list like any other, and that list going empty
    // would read as every source file being well-formed.
    expect(everySourceFile())->not->toBe([]);

    // The reader the walk depends on, asked about a file whose answer is known.
    expect(Imports::declaredNames(Tree::at('tests/Support/Imports.php')))->toBe(['Imports']);
});
