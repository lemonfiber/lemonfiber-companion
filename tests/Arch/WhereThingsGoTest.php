<?php

declare(strict_types=1);

use Tests\Support\Module;
use Tests\Support\Tree;

// W1 to W4 — where a file lives is not a preference.
//
// Every other rule on this page is derived from a path or a namespace: the
// module kind rules read `app-modules/<name>/composer.json`, the published
// surface rule reads `Api` against `Internal`, H4 pairs a test with its source
// by replacing one path segment. A file in the wrong place is not untidy — it
// is a file that the rules governing its neighbours do not reach, and nothing
// says so, because a rule that finds no files reports a green tick.

it('W1 — app/ is the composition root and nothing else', function (): void {
    $strays = [];

    foreach (Tree::filesUnder(Tree::at('app'), '.php') as $file) {
        if (! str_contains($file, '/app/Providers/')) {
            $strays[] = str_replace(sprintf('%s/', Tree::root()), '', $file);
        }
    }

    expect($strays)->toBe([], sprintf(
        "These sit in app/ without being the composition root:\n  %s\n\n"
        . 'app/ holds App\\Providers and nothing else. It is the one place permitted to '
        . 'name both a port and the adapter behind it, and that permission is granted by '
        . 'path — so a class put here quietly acquires it, along with an exemption from '
        . 'A2, A3 and A4. Domain logic belongs in a capability module, a screen in a '
        . 'surface module, and anything that talks to the outside in an adapter (W1).',
        implode("\n  ", $strays),
    ));
});

it('W2 — a module declares only its own namespace', function (): void {
    $strays = [];

    foreach (Module::all() as $module) {
        foreach ($module->classes() as $file) {
            $declared = namespaceOf($file);

            if ($declared !== '' && ! str_starts_with($declared, $module->namespace)) {
                $strays[] = sprintf('%s declares %s', $file, $declared);
            }
        }
    }

    expect($strays)->toBe([], sprintf(
        "These live in one module and answer to another:\n  %s\n\n"
        . "A module's boundary rules are generated from its manifest and applied to its "
        . 'own namespace, so a class sitting in one module under another\'s namespace is '
        . 'governed by neither: the rules for where it lives do not match its name, and '
        . 'the rules for its name do not look where it lives (W2, E1).',
        implode("\n  ", $strays),
    ));
});

it('W3 — the root holds suites, not scattered tests', function (): void {
    // Each of these is a suite in phpunit.xml with a job attached to it. A
    // directory that is not one is a directory nothing runs.
    $suites = ['Arch', 'Templates', 'Contract', 'Feature', 'Floors', 'Guards', 'Support'];
    $strays = [];

    $entries = scandir(Tree::at('tests'));

    foreach ($entries === false ? [] : $entries as $entry) {
        if ($entry === '.' || $entry === '..' || ! is_dir(Tree::at(sprintf('tests/%s', $entry)))) {
            continue;
        }

        if (! in_array($entry, $suites, strict: true)) {
            $strays[] = sprintf('tests/%s', $entry);
        }
    }

    foreach (Tree::filesUnder(Tree::at('resources/views'), '.blade.php') as $view) {
        $strays[] = str_replace(sprintf('%s/', Tree::root()), '', $view);
    }

    expect($strays)->toBe([], sprintf(
        "These are in the root and belong to a module:\n  %s\n\n"
        . 'Root tests are the ones that cannot belong to a module: the rules, the '
        . 'templates, the port contracts, and the composition root. Everything else is a '
        . "module testing itself, next to itself.\nA Blade file in root resources/views "
        . 'is the same mistake: a screen belongs to the surface module that navigates to '
        . "it, and templates/ finds it there (W3, H4).",
        implode("\n  ", $strays),
    ));
});

it('W4 — a module test answers to its module', function (): void {
    $strays = [];

    foreach (Module::all() as $module) {
        foreach ($module->testFiles() as $file) {
            $declared = namespaceOf($file);

            // A Pest file declares no namespace, which is the normal case and
            // is fine. What is refused is declaring somebody else's.
            if ($declared !== '' && ! str_starts_with($declared, sprintf('%s\\Tests', $module->namespace))) {
                $strays[] = sprintf('%s declares %s', $file, $declared);
            }
        }
    }

    expect($strays)->toBe([], sprintf(
        "These tests answer to a module they do not live in:\n  %s\n\n"
        . "A module's tests are namespaced for that module, which is what the manifest's "
        . 'own PSR-4 mapping says and what makes a test findable from the class it pins '
        . '(W4, H4).',
        implode("\n  ", $strays),
    ));
});

/** The namespace a file declares, or an empty string where it declares none. */
function namespaceOf(string $file): string
{
    $source = file_get_contents($file);

    if (! is_string($source)) {
        return '';
    }

    return preg_match('/^namespace\s+([^;]+);/m', $source, $found) === 1 ? trim($found[1]) : '';
}
