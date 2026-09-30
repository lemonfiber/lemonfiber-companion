<?php

declare(strict_types=1);

use Tests\Support\Imports;
use Tests\Support\Module;
use Tests\Support\Stores;
use Tests\Support\Tree;

// A11 — a store is reached through its port, and bound in one place.
//
// A capability's store sits inside the capability, so the language would let
// any class beside it construct the query class and call it. That is the one
// thing the separate module used to make impossible, and it is what keeps the
// decisions testable: a decision that names its store can be run only over a
// database, and one that asks the port its module declares can be run over a
// fake. So nothing in the module outside `src/Internal/Store` names a class in
// it, no other module does either, and the composition root is the one file
// that binds the port to the store.
//
// Names are resolved as PHP resolves them rather than read off the imports: a
// class in `Modules\Health\Internal` reaches the store as `Store\...` without
// importing anything.

/** The one file that may name a store from outside it. */
const WHERE_A_STORE_IS_BOUND = 'bootstrap/Composition/CompositionRoot.php';

/**
 * Every file the application runs outside a store, which may name none.
 *
 * @return array<string, Module|null> file => the module it belongs to, or none for the composition root
 */
function everyFileOutsideAStore(): array
{
    $files = [];

    foreach (Module::all() as $module) {
        foreach ($module->classes() as $file) {
            $files[$file] = $module;
        }
    }

    foreach (Tree::filesUnder(Tree::at('bootstrap/Composition'), '.php') as $file) {
        if ($file !== Tree::at(WHERE_A_STORE_IS_BOUND)) {
            $files[$file] = null;
        }
    }

    return $files;
}

it('finds the stores to wall off, and every one bound where a store is bound', function (): void {
    // The floor. With no store found, the rule below reads every file and
    // judges it against nothing; and a store the composition root does not
    // name is one no port reaches.
    $bound = Stores::namesIn(Tree::at(WHERE_A_STORE_IS_BOUND));

    expect(Stores::all())->not->toBe([])
        ->and(array_values(array_filter(
            Stores::all(),
            static fn(Module $store): bool => ! Imports::anyUnder($bound, Stores::namespaceOf($store)),
        )))->toBe([]);
});

it('A11 — nothing outside a store names a class in it, but the composition root', function (): void {
    $offenders = [];

    foreach (everyFileOutsideAStore() as $file => $module) {
        $names = Stores::namesIn($file);

        foreach (Stores::all() as $store) {
            if ($module instanceof Module && $module->name === $store->name && Stores::holds($module, $file)) {
                continue;
            }

            if (Imports::anyUnder($names, Stores::namespaceOf($store))) {
                $offenders[] = sprintf('%s names %s', $file, Stores::namespaceOf($store));
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These reach a store past its port:\n  %s\n\n"
        . 'A capability asks the port it declares, and the composition root binds that port to '
        . 'the store under src/Internal/Store. A decision that names the store can only be run '
        . 'over a database; one that asks the port can be run over a fake (A11).',
        implode("\n  ", $offenders),
    ));
});
