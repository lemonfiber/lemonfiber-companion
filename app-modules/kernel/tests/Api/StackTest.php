<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotNamed;

use function str_repeat;

const A_STACK_NONCE = 'b2c3d4e5f6071829';

/**
 * The certificate this stack promises.
 *
 * A function rather than a constant: a module's test files share one namespace,
 * so `A_DIGEST` here and `A_DIGEST` in `FingerprintTest` are one name declared
 * twice and a fatal the moment both load — which is what happened, and what
 * `G10` exists to catch one file earlier.
 */
function aDigest(): Fingerprint
{
    return Fingerprint::of(str_repeat('ab', Fingerprint::CHARACTERS / 2));
}

function aStack(string $called = 'The loft', string $nonce = A_STACK_NONCE): Stack
{
    return Stack::of(
        StackId::of(Nonce::of($nonce)),
        $called,
        Address::of('https://192.168.1.42:8443'),
        aDigest(),
    );
}

it('N1-R11 — carries the four things that make one stack', function (): void {
    $stack = aStack();

    expect($stack->id()->stored())->toBe(A_STACK_NONCE);
    expect($stack->name())->toBe('The loft');
    expect($stack->at()->forTheClient())->toBe('https://192.168.1.42:8443');
    expect($stack->presents()->is(aDigest()))->toBeTrue();
});

it('N1-R23 — does not carry the session', function (): void {
    // The obvious place to keep it, and that is exactly why it is not kept
    // here. A stack is what the app remembers between launches and a session is
    // what it may not — one value holding both makes the thing that must be
    // persisted and the thing that must not be the same object, and the first
    // code to write one out takes the other with it.
    //
    // Pinned rather than left to reading, because the pressure to add it will
    // arrive from a screen that has a stack and wants a header, and the change
    // is one property wide.
    // `recognises` joins the list deliberately. It answers a question about this
    // stack's own identity — whether the machine that replied presented the
    // certificate pinned at pairing (`N1-R19`) — and `N1-R22` puts that on the
    // stack rather than on the address, so that reaching the same machine by
    // another route does not re-open the question.
    expect(get_class_methods(Stack::class))
        ->toBe(['of', 'id', 'name', 'at', 'presents', 'recognises', 'is']);
});

it('refuses a stack an operator cannot tell from another', function (): void {
    // A name is the only thing an operator has to tell two of them apart by —
    // "192.168.1.42" and "192.168.1.43" are not two names, which is why the
    // address is not allowed to stand in for one.
    expect(fn(): Stack => aStack(called: '   '))
        ->toThrow(StackIsNotNamed::class, 'no name');
});

it('takes the name as the operator typed it, less the accident', function (): void {
    expect(aStack(called: '  The loft  ')->name())->toBe('The loft');
});

it('N1-R22 — is the same stack wherever it answers', function (): void {
    // Identity is the id, not the address. The same machine reached by another
    // route is the same machine; an address makes a fine key right up to the
    // first DHCP lease, at which point every retained reading silently belongs
    // to something else.
    $moved = Stack::of(
        StackId::of(Nonce::of(A_STACK_NONCE)),
        'The loft, moved',
        Address::of('https://10.0.0.7:8443'),
        aDigest(),
    );

    expect(aStack()->is($moved))->toBeTrue();
    expect(aStack()->is(aStack(nonce: 'c3d4e5f607182930')))->toBeFalse();
});
