<?php

declare(strict_types=1);

use Tests\Support\Imports;
use Tests\Support\Kind;
use Tests\Support\Module;
use Tests\Support\Stores;
use Tests\Support\Tree;

// A1, A10 and A14 — the database belongs to the stores, and each table to one owner.
//
// What the phone keeps is decided and stored by the capability it belongs to.
// Its store sits inside it, under `src/Internal/Store`, beside the module's own
// `database/migrations`, and two things keep that arrangement from eroding.
// The database is named nowhere else, so the rest of a capability, a screen or
// an adapter cannot quietly start keeping something on its own; and each table
// belongs to the one module whose migrations create it, so a store cannot grow
// by reading another's rows — one owner that needs another's data asks that
// owner's capability.
//
// A store is recognised by where it is rather than by a list here, so one
// written tomorrow is governed the moment its first class exists.
//
// What every store of readings does over its table is written once, in a store
// kit, and the kit is held to the same wall from the other side: a store names
// it, and nothing else does — not the rest of a capability, not another kind of
// module, and not the composition root.

/** The prefix every table a capability owns carries: its own name. */
function thePrefixOf(Module $owner): string
{
    return sprintf('%s_', str_replace('-', '_', $owner->name));
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
 * Every table a module's migrations create, with the module that creates it.
 *
 * @return array<string, Module> table => the module that owns it
 */
function everyTableAndItsOwner(): array
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
    expect(Stores::all())->not->toBe([])
        ->and(everyTableAndItsOwner())->not->toBe([]);
});

it('A1 — Illuminate\Database is named only in a capability\'s store and its migrations, and in a store kit', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach (everyFileAModuleRuns($module) as $file) {
            if ($module->kind === Kind::StoreKit || Stores::holds($module, $file) || Stores::isAMigrationOf($module, $file)) {
                continue;
            }

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
        . 'What the phone keeps is decided and stored by the capability it belongs to. Its store '
        . 'is one query class under that capability\'s src/Internal/Store, beside its own '
        . 'database/migrations, answering a port the capability declares, and the queries every such '
        . 'store shares are in a store kit; nothing else names the database (A1).',
        implode("\n  ", $offenders),
    ));
});

it('A10 — a table carries its owner\'s prefix and is created only in its owner\'s migrations', function (): void {
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach (everyFileAModuleRuns($module) as $file) {
            foreach (theTablesCreatedIn($file) as $table) {
                if (! Stores::isAMigrationOf($module, $file)) {
                    $offenders[] = sprintf('%s creates %s, and only a capability\'s own migrations create a table', $file, $table);

                    continue;
                }

                if (! str_starts_with($table, thePrefixOf($module))) {
                    $offenders[] = sprintf('%s creates %s, which does not carry %s', $file, $table, thePrefixOf($module));
                }
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These tables do not belong to one owner:\n  %s\n\n"
        . 'A table is created by the capability that owns it, in that capability\'s '
        . 'database/migrations, and is named with its owner\'s prefix, so a row on disk says '
        . 'whose it is (A10).',
        implode("\n  ", $offenders),
    ));
});

it('A10 — no module names a table another module owns', function (): void {
    $tables = everyTableAndItsOwner();
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

/**
 * Every module of the store kit's kind.
 *
 * @return list<Module>
 */
function everyStoreKit(): array
{
    return array_values(array_filter(Module::all(), static fn(Module $module): bool => $module->kind === Kind::StoreKit));
}

/**
 * The store kits a file names, resolved as PHP resolves its names.
 *
 * @param list<Module> $kits
 *
 * @return list<string>
 */
function theStoreKitsNamedIn(string $file, array $kits): array
{
    $names = Stores::namesIn($file);

    return array_values(array_map(
        static fn(Module $kit): string => sprintf('%s names %s', $file, $kit->namespace),
        array_filter($kits, static fn(Module $kit): bool => Imports::anyUnder($names, $kit->namespace)),
    ));
}

it('A14 — a store kit is named only in a capability\'s store', function (): void {
    $kits = everyStoreKit();
    $offenders = [];

    foreach (Module::all() as $module) {
        foreach (everyFileAModuleRuns($module) as $file) {
            if ($module->kind !== Kind::StoreKit && ! Stores::holds($module, $file)) {
                $offenders = [...$offenders, ...theStoreKitsNamedIn($file, $kits)];
            }
        }
    }

    foreach (Tree::filesUnder(Tree::at('bootstrap/Composition'), '.php') as $file) {
        $offenders = [...$offenders, ...theStoreKitsNamedIn($file, $kits)];
    }

    expect($kits)->not->toBe([])
        ->and($offenders)->toBe([], sprintf(
            "These name a store kit without being a store:\n  %s\n\n"
            . 'A store kit holds what every capability\'s store of readings does over its own table, '
            . 'and it is reached from a capability\'s src/Internal/Store and nowhere else: the rest of '
            . 'the capability asks the port its module declares, and the composition root binds that '
            . 'port to the store (A14).',
            implode("\n  ", $offenders),
        ));
});
