<?php

declare(strict_types=1);

use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\Module;

// Discarding retained state must not discard a pairing or its pinned
// fingerprint; where those cannot be carried forward, the app must say that
// re-pairing is required and why.
//
// Two ports keep retained state and they have opposite obligations. `SecureStorage`
// keeps a session, which the app may hold and must not spread — so it has
// `forget()`, and signing out is a real thing an operator does. `Stacks` keeps
// the pairing: an identity, a name somebody typed, an address and a pinned
// certificate, which is exactly what must survive.
//
// One act takes a pairing away, and it is not a discard of retained state:
// the operator removing a stack from the phone on its Stack settings page,
// which asks first and says the stack itself keeps running. So the port that
// keeps a pairing declares exactly one removing method, `forgetTheStack()`,
// and nothing but that removal calls it. Clearing what the phone keeps asks
// its own set of stores, which holds no pairing; that is held where the set
// is made.

/** The words a method uses when it takes something away. */
const A_METHOD_THAT_REMOVES = ['forget', 'remove', 'delete', 'discard', 'clear', 'drop', 'unpair'];

/**
 * Every method of a class or interface whose name says it removes something.
 *
 * @param class-string $named
 *
 * @return list<string>
 */
function everyRemovingMethodOf(string $named): array
{
    $found = [];

    foreach (new ReflectionClass($named)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        foreach (A_METHOD_THAT_REMOVES as $verb) {
            if (str_starts_with(mb_strtolower($method->getName()), $verb)) {
                $found[] = $method->getName();
            }
        }
    }

    sort($found);

    return $found;
}

it('the port that keeps a pairing offers one way to take it away, and only that', function (): void {
    expect(everyRemovingMethodOf(Stacks::class))->toBe(['forgetTheStack'], sprintf(
        "`Stacks` declares these, and each of them takes a pairing away:\n  %s\n\n"
        . '`N1-R34` says a discard of retained state must not take the pairing or its pinned '
        . "fingerprint with it. Removing a stack from the phone is the one act that does, and it has one method.\n",
        implode("\n  ", everyRemovingMethodOf(Stacks::class)),
    ));
});

it('the adapter that writes a pairing down offers no other way to take it away either', function (): void {
    // The same failure one layer down and reachable by anything in that module,
    // which is why the port alone is not enough to ask.
    expect(everyRemovingMethodOf(PlatformStacks::class))->toBe(['forgetTheStack']);
});

it('the port that keeps a session still offers signing out beside the removal, which is the distinction', function (): void {
    // Without this the rule above passes just as well on a codebase where
    // nothing can be forgotten at all — including a session, which expects to
    // be. The two ports are separate precisely so one may forget and the
    // other may not, and a rule that could not tell them apart would be
    // describing an accident.
    expect(everyRemovingMethodOf(SecureStorage::class))->toBe(['forget', 'forgetTheStack']);
});

it('nothing but removing a stack from the phone asks to forget one', function (): void {
    $root = dirname(__DIR__, 2);
    $composition = glob(sprintf('%s/bootstrap/Composition/*.php', $root));
    $sources = $composition === false ? [] : $composition;

    foreach (Module::all() as $module) {
        $sources = [...$sources, ...$module->classes()];
    }

    $callers = [];

    foreach ($sources as $file) {
        if (str_contains((string) file_get_contents($file), '->forgetTheStack(')) {
            $callers[] = str_replace(sprintf('%s/', $root), '', $file);
        }
    }

    sort($callers);

    expect($callers)->toBe([
        'app-modules/connection/src/Api/RemovingAStack.php',
        'bootstrap/Composition/EveryKeeperOfAStack.php',
    ]);
});
