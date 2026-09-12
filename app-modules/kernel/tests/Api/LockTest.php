<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Authenticated;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Lock;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Which side the lock is on, as a word.
 *
 * Named for this file rather than `fold`, because a module's test files share
 * one namespace and two of the same name are a fatal the moment both load (G10).
 */
function whichSide(Lock $lock): string
{
    return $lock->either(
        held: static fn(): Code => Code::of('held'),
        open: static fn(): Code => Code::of('open'),
    )->shown();
}

it('N4-R19 — the app starts held, because that is what forgetting gets you', function (): void {
    // A cold start requires the device's own authentication, and the safe state
    // is the one you reach by not deciding.
    expect(whichSide(Lock::held()))->toBe('held');
});

it('N4-R8 — the only way open is a device that said yes', function (): void {
    expect(whichSide(Lock::openedBy(Authenticated::byTheDevice())))->toBe('open');
});

it('N4-R8 — there is no way to open a lock from a boolean', function (): void {
    // The requirement in the form a rule can hold. A biometric failure must not
    // fall back to unlocked, and the way to make that structural is to give the
    // opening constructor a parameter that a failure cannot produce.
    //
    // Pinned over the published surface rather than left to reading, because
    // every line this refuses is a plausible one: `Lock::open()`,
    // `$lock->unlock($prompt->succeeded())`, or an `if (! $unlocked)` whose
    // fall-through is where an app ends up open because nothing said to close
    // it.
    expect(get_class_methods(Lock::class))->toBe(['held', 'openedBy', 'either']);

    $takes = new ReflectionMethod(Lock::class, 'openedBy')->getParameters()[0]->getType();

    expect($takes instanceof ReflectionNamedType ? $takes->getName() : null)
        ->toBe(Authenticated::class);
});

it('N4-R8 — proof of authentication cannot be built from anything', function (): void {
    // `Authenticated` exists to be hard to obtain: a private constructor and one
    // named maker an adapter calls on a prompt that returned success. That is a
    // thin guarantee and it is the right thin one — the mistake now lives in a
    // file written to be read carefully, rather than at every call site holding
    // a boolean.
    expect(get_class_methods(Authenticated::class))->toBe(['byTheDevice']);

    expect(new ReflectionMethod(Authenticated::class, 'byTheDevice')->getNumberOfParameters())
        ->toBe(0);
});

it('N4-R7 — there is no accessor to forget to check', function (): void {
    // A boolean accessor is the check somebody forgets, and the forgotten one
    // here shows a stack's contents to whoever picked up the phone.
    expect(get_class_methods(Lock::class))->not->toContain('isOpen');
});
