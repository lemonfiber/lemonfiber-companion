<?php

declare(strict_types=1);

use Tests\Support\Imports;
use Tests\Support\Kind;
use Tests\Support\Module;
use Tests\Support\Tree;

// A1 and A10 — the database belongs to the stores, and each table to one store.
//
// What the phone keeps is decided by the capability it belongs to and stored
// by a small adapter of its own, which adapts Laravel's database and nothing
// else. Two things keep that arrangement from eroding. The database is named
// in no module but a store, so a capability or a screen cannot quietly start
// keeping something on its own; and each table belongs to the one store that
// creates it, so a store cannot grow by reading another's rows — one owner
// that needs another's data asks that owner's capability.
//
// A store is an adapter whose manifest requires `illuminate/database`: the one
// package an adapter adapts is the one its manifest names, so the declaration
// is what makes a module a store, and a module that names the database without
// making it is refused here by name.

/** The package a store adapts. */
const WHAT_A_STORE_ADAPTS = 'illuminate/database';

/** Whether a module is a store: an adapter whose manifest requires the database. */
function isAStore(Module $module): bool
{
    $manifest = json_decode((string) file_get_contents(sprintf('%s/composer.json', $module->path)), associative: true);

    return $module->kind === Kind::Adapter
        && is_array($manifest)
        && array_key_exists('require', $manifest)
        && is_array($manifest['require'])
        && array_key_exists(WHAT_A_STORE_ADAPTS, $manifest['require']);
}

/** The prefix every table a store owns carries: its owner's name, which is the store's without `-kept`. */
function thePrefixOf(Module $store): string
{
    return sprintf('%s_', str_replace('-', '_', preg_replace('/-kept$/', '', $store->name) ?? $store->name));
}

/**
 * Every PHP file a module holds that the application runs: its sources and its migrations.
 *
 * @return list<string>
 */
function everyFileAModuleRuns(Module $module): array
{
    return [
        ...Tree::filesUnder(sprintf('%s/src', $module->path), '.php'),
        ...Tree::filesUnder(sprintf('%s/database', $module->path), '.php'),
    ];
}

/**
 * Every table a file creates, by name.
 *
 * @return list<string>
 */
function theTablesCreatedIn(string $file): array
{
    preg_match_all('/Schema::create\(\s*[\'"]([^\'"]+)[\'"]/', (string) file_get_contents($file), $created);

    return $created[1];
}

/**
 * Every string a file spells out, read over tokens so a comment naming a table is not a use of it.
 *
 * @return list<string>
 */
function theStringsSpelledIn(string $file): array
{
    $strings = [];

    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $strings[] = substr($token[1], 1, -1);
        }
    }

    return $strings;
}

/**
 * Every table the stores' migrations create, with the store that creates it.
 *
 * @return array<string, Module> table => the store that owns it
 */
function everyTableAndItsStore(): array
{
    $owned = [];

    foreach (Module::all() as $module) {
        foreach (Tree::filesUnder(sprintf('%s/database/migrations', $module->path), '.php') as $file) {
            foreach (theTablesCreatedIn($file) as $table) {
                $owned[$table] = $module;
            }
        }
    }

    return $owned;
}

it('finds the stores and the tables they own', function (): void {
    // The floor. A reading that found no store, or no table, would make every
    // rule below pass about nothing.
    expect(array_filter(Module::all(), isAStore(...)))->not->toBe([])
        ->and(everyTableAndItsStore())->not->toBe([]);
});

it('A1 — Illuminate\Database is named only in a store adapter', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        if (isAStore($module)) {
            continue;
        }

        foreach (everyFileAModuleRuns($module) as $file) {
            if (Imports::anyUnder(Imports::of($file), 'Illuminate\Database')) {
                $offenders[] = sprintf('%s names Illuminate\Database', $file);
            }
        }
    }

    foreach (Tree::filesUnder(Tree::at('bootstrap/Composition'), '.php') as $file) {
        if (Imports::anyUnder(Imports::of($file), 'Illuminate\Database')) {
            $offenders[] = sprintf('%s names Illuminate\Database', $file);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These reach the database without being a store:\n  %s\n\n"
        . 'What the phone keeps is decided by the capability it belongs to and stored by an adapter '
        . 'of its own, which requires %s in its manifest and adapts nothing else. Take what this '
        . 'needs as a port in Modules\Kernel\Api and let a store implement it (A1).',
        implode("\n  ", $offenders),
        WHAT_A_STORE_ADAPTS,
    ));
});

it('A10 — a table carries its owner\'s prefix and is created only in its store\'s migrations', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach (everyFileAModuleRuns($module) as $file) {
            foreach (theTablesCreatedIn($file) as $table) {
                if (! isAStore($module) || ! str_contains($file, sprintf('%s/database/migrations/', $module->path))) {
                    $offenders[] = sprintf('%s creates %s, and only a store\'s own migrations create a table', $file, $table);

                    continue;
                }

                if (! str_starts_with($table, thePrefixOf($module))) {
                    $offenders[] = sprintf('%s creates %s, which does not carry %s', $file, $table, thePrefixOf($module));
                }
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These tables do not belong to one store:\n  %s\n\n"
        . 'A table is created by the store that owns it, in that store\'s database/migrations, and '
        . 'is named with its owner\'s prefix, so a row on disk says whose it is (A10).',
        implode("\n  ", $offenders),
    ));
});

it('A10 — no module names a table another module owns', function (): void {
    $tables = everyTableAndItsStore();
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach (everyFileAModuleRuns($module) as $file) {
            foreach (theStringsSpelledIn($file) as $spelled) {
                if (array_key_exists($spelled, $tables) && $tables[$spelled]->name !== $module->name) {
                    $offenders[] = sprintf('%s names %s, which %s owns', $file, $spelled, $tables[$spelled]->name);
                }
            }
        }
    }

    foreach (Tree::filesUnder(Tree::at('bootstrap/Composition'), '.php') as $file) {
        foreach (theStringsSpelledIn($file) as $spelled) {
            if (array_key_exists($spelled, $tables)) {
                $offenders[] = sprintf('%s names %s, which %s owns', $file, $spelled, $tables[$spelled]->name);
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These name a table they do not own:\n  %s\n\n"
        . 'A store reads its own rows and nobody else\'s. One owner that needs another\'s data asks '
        . 'that owner\'s capability, which asks its store (A10).',
        implode("\n  ", $offenders),
    ));
});
