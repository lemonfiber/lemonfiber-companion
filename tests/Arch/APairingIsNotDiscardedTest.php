<?php

declare(strict_types=1);

use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\Vault\Api\PlatformStacks;

// Discarding retained state must not discard a pairing or its pinned
// fingerprint; where those cannot be carried forward, the app must say that
// re-pairing is required and why.
//
// Two ports keep retained state and they have opposite obligations. `SecureStorage`
// keeps a session, which the app may hold and must not
// spread — so it has `forget()`, and signing out is a real thing an operator
// does. `Stacks` keeps the pairing: an identity, a name somebody typed, an
// address and a pinned certificate, which is exactly what must survive.
//
// `Stacks` says in prose that it has a read and a write and no delete, and that
// forgetting a stack arrives with the screen that offers it. Nothing held that.
// A delete added to the port without the screen would be the requirement broken
// in the quietest possible way: no operator asked for it, nothing on a phone
// changes, and the next piece of code that wants to tidy up retained state has
// a method that takes a pairing with it.
//
// So the rule is on the shape rather than on the prose: the port that keeps a
// pairing declares nothing that removes one, and the port that keeps a session
// still does. Asked of the adapter too, because a platform store growing a
// delete is the same failure one layer down, reachable by anything in that
// module.

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

it('N1-R34 — the port that keeps a pairing offers no way to discard one', function (): void {
    expect(everyRemovingMethodOf(Stacks::class))->toBe([], sprintf(
        "`Stacks` declares these, and each of them takes a pairing away:\n  %s\n\n"
        . '`N1-R34` says a discard of retained state must not take the pairing or its pinned '
        . 'fingerprint with it, and that where they cannot be carried forward the app says '
        . "re-pairing is required and why.\n"
        . 'A port method with no screen behind it is a promise no adapter has been held to — '
        . "so this arrives with the screen that offers it, and with that sentence, or not at all.\n",
        implode("\n  ", everyRemovingMethodOf(Stacks::class)),
    ));
});

it('N1-R34 — nor does the adapter that writes it down', function (): void {
    // The same failure one layer down and reachable by anything in that module,
    // which is why the port alone is not enough to ask.
    expect(everyRemovingMethodOf(PlatformStacks::class))->toBe([]);
});

it('the port that keeps a session still offers one, which is the distinction', function (): void {
    // Without this the rule above passes just as well on a codebase where
    // nothing can be forgotten at all — including a session, which
    // expects to be. The two ports are separate precisely so one may forget and
    // the other may not, and a rule that could not tell them apart would be
    // describing an accident.
    expect(everyRemovingMethodOf(SecureStorage::class))->toBe(['forget']);
});
