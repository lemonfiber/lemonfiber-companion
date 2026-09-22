<?php

declare(strict_types=1);

use Tests\Support\ApiSurface;
use Tests\Support\MeasuredTree;
use Tests\Support\Module;
use Tests\Support\Template;
use Tests\Support\Tree;

// A rule that found nothing is not a rule that passed.
//
// Most rules here discover what they judge: every module, every published
// class, every comment line, every test file. Discovery has one failure mode
// that looks exactly like success — it finds nothing, there is nothing to
// refuse, and the rule reports a pass. Nobody sees it happen, because a green
// tick is what a rule looks like when it is working.
//
// It is not hypothetical here. `Module::all()` reads the composer manifests, and
// a worktree with a stale autoloader has already made module discovery answer
// wrong once in this project; a mistaken `Tree::root()` would silence every rule
// resting on it in the same instant. What makes that the dangerous case rather
// than an annoying one is the breadth: not one rule going quiet, but all of them
// together, in a run that says 500 tests passed.
//
// So the foundation is asserted directly. This proves nothing about any
// individual rule's own filter — a rule narrowing to a namespace nobody uses is
// still silent, and each of those is its own business — but it does mean the
// ground underneath them is never silently empty.
//
// Counted rather than merely non-empty where a number is known and stable
// enough to mean something. A floor rather than an exact count: an exact count
// is a number somebody edits to make a red run green.

it('Q-R66 — the modules a rule judges are found', function (): void {
    // `Module::all()` is under `ModuleApiTest`, `ModuleBoundariesTest`,
    // `WhereThingsGoTest`, `TestsMirrorSourceTest` and the composition-root
    // rules. Empty, every one of them passes.
    expect(Module::all())->not->toBe([]);
    expect(Module::populated())->not->toBe([]);
    expect(Module::namespaces())->not->toBe([]);
});

it('Q-R66 — the trees held to a floor are found, and the two that are not modules are among them', function (): void {
    // `MeasuredTree::all()` is what `G7` holds to a bar, what the `Floors`
    // suite measures against the clover report and what `scripts/mutation.php`
    // mutates. Empty, all three pass having judged nothing.
    expect(MeasuredTree::all())->not->toBe([]);

    // Named, and deliberately. Every other list in this repository is derived
    // so that it cannot lose a tree quietly — and this one is derived from
    // `phpunit.xml`, which means a tree deleted *there* is lost from all three
    // gates at once and from the coverage report in the same edit. Nothing
    // about that is loud: `--min=100` still passes, over less.
    //
    // These two are the ones worth naming because they are the two that were
    // already missing. The floors read `Module::all()`, `bridge/src` is a path
    // package and `bootstrap/Composition` is the composition root, so 2,900
    // lines sat inside the coverage floor and outside every per-directory bar.
    $measured = array_map(static fn(MeasuredTree $tree): string => $tree->path, MeasuredTree::all());

    expect($measured)->toContain('bridge/src')
        ->toContain('bootstrap/Composition');
});

it('Q-R66 — a populated module answers with the classes it declares', function (): void {
    // The second half, and the one a stale autoloader breaks: modules are found
    // and each reports no classes, so every rule about published surfaces holds
    // vacuously.
    $declaring = array_filter(
        Module::populated(),
        static fn(Module $module): bool => $module->classNames() !== [],
    );

    expect($declaring)->not->toBe([]);
});

it('Q-R66 — the published surface the API rules judge is found', function (): void {
    expect(ApiSurface::classesIn())->not->toBe([]);
});

it('Q-R66 — the trees the file rules read are found', function (): void {
    // `Tree::root()` resolving wrong takes every one of these at once, which is
    // why they are asserted together rather than beside the rules that use them.
    expect(Tree::filesUnder(Tree::at('app-modules'), '.php'))->not->toBe([]);
    expect(Tree::filesUnder(Tree::at('tests'), '.php'))->not->toBe([]);
    expect(Tree::filesUnder(Tree::at('bridge/resources'), '.kt'))->not->toBe([]);
    expect(Tree::filesUnder(Tree::at('bridge/resources'), '.swift'))->not->toBe([]);
    expect(Tree::testFiles())->not->toBe([]);
});

it('Q-R66 — the root is this repository rather than wherever the run started', function (): void {
    // The one that makes the rest of this file meaningful. Every path above is
    // built from `Tree::root()`, so a root pointing somewhere plausible-but-wrong
    // — a parent directory, a sibling worktree — produces file lists that are
    // non-empty and about the wrong tree.
    expect(Tree::isTheRepository(Tree::root()))->toBeTrue();

    // And the judgement is watched refusing, on the directory it would actually
    // be handed: the parent, which is what `dirname(__DIR__, 2)` answers if this
    // file ever moves one level down. Asserting only the line above would hold
    // just as well for a check that says yes to everything, which is the failure
    // every rule in this file is about.
    expect(Tree::isTheRepository(dirname(Tree::root())))->toBeFalse();
    expect(Tree::isTheRepository(sys_get_temp_dir()))->toBeFalse();
});

it('Q-R66 — the templates the Blade rules read are found', function (): void {
    // Five rules in `tests/Templates` read `Template::all()` and one of them
    // asserts it found anything. There is one Blade template in this
    // repository, so the distance between "checks every screen's markup" and
    // "checks nothing" is a directory rename.
    //
    // What that looks like is worse than a pass: with the template moved away
    // the whole suite reports one skipped test and zero assertions, because
    // four of the five are driven by a dataset that is then empty. A skipped
    // suite is green, and greener than a passing one is — there is not even a
    // tick to count.
    expect(Template::all())->not->toBe([]);
});
