<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function mb_strlen;

use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\CredentialIsBlank;
use Modules\Kernel\Api\CredentialIsSpent;
use Modules\Kernel\Api\MustNotLeaveThisProcess;

use function print_r;
use function serialize;
use function sprintf;
use function unserialize;
use function var_export;

const WHAT_THE_STACK_GAVE = 'a-credential-not-a-session';

it('N1-R7 — answers once, for the one exchange it exists for', function (): void {
    expect(Credential::of(WHAT_THE_STACK_GAVE)->forTheExchange())->toBe(WHAT_THE_STACK_GAVE);
});

it('N1-R7 — keeps nothing to re-send', function (): void {
    // The clause that takes a design. Exchanging a credential is ordinary; not
    // retaining it is not, because retention is what every value does by
    // default — and the line that re-sends it on the next request is one
    // somebody writes while fixing something else.
    $credential = Credential::of(WHAT_THE_STACK_GAVE);

    $credential->forTheExchange();

    expect(fn(): string => $credential->forTheExchange())
        ->toThrow(CredentialIsSpent::class, 'already been exchanged');
});

it('N1-R7 — says whether it has been spent', function (): void {
    $credential = Credential::of(WHAT_THE_STACK_GAVE);

    expect($credential->wasSpent())->toBeFalse();

    $credential->forTheExchange();

    expect($credential->wasSpent())->toBeTrue();
});

it('N1-R7 — forgets before it answers, not after', function (): void {
    // The order matters and is not decoration. If the secret were cleared after
    // the return, anything throwing downstream would leave it still held — and a
    // caller catching that exception could ask again. Asserted by reading the
    // state from inside the same expression that takes the value.
    $credential = Credential::of(WHAT_THE_STACK_GAVE);
    $spentWhileAnswering = null;

    $taken = (static function () use ($credential, &$spentWhileAnswering): string {
        $said = $credential->forTheExchange();
        $spentWhileAnswering = $credential->wasSpent();

        return $said;
    })();

    expect($taken)->toBe(WHAT_THE_STACK_GAVE)
        ->and($spentWhileAnswering)->toBeTrue();
});

it('refuses material that carried no credential', function (): void {
    expect(fn(): Credential => Credential::of('   '))
        ->toThrow(CredentialIsBlank::class, 'empty credential');
});

it('N1-R15 — its refusal says nothing about what arrived', function (): void {
    // An exception carrying a credential puts it in a stack trace, and a stack
    // trace is exactly what ends up in a diagnostic report.
    expect(CredentialIsBlank::inPairingMaterial()->getMessage())->not->toContain(WHAT_THE_STACK_GAVE);
});

it('N1-R23 — cannot be written out by anything that does not ask', function (): void {
    // `serialize()` is how a value reaches a cache without anybody deciding it
    // should, and a credential in a cache can be replayed — unlike a session,
    // nothing on the server expires it on a schedule.
    $credential = Credential::of(WHAT_THE_STACK_GAVE);

    expect(fn(): string => serialize($credential))
        ->toThrow(MustNotLeaveThisProcess::class, 'may not be serialised');
});

it('N1-R23 — cannot be read back in either', function (): void {
    // The other half of the same door. Without it a crafted payload naming this
    // class walks back into an unspent credential nobody was given.
    // Built from the class name's own length rather than counted by hand: a
    // payload whose length prefix is wrong is refused by the parser before
    // `__unserialize` is ever reached, so the test would pass while proving
    // nothing about this class. That is how the first version of this was wrong.
    $written = sprintf('O:%d:"%s":0:{}', mb_strlen(Credential::class), Credential::class);

    expect(fn(): mixed => unserialize($written))
        ->toThrow(MustNotLeaveThisProcess::class, 'may not be serialised');
});

it('N1-R15 — hides itself from the reader that honours __debugInfo', function (): void {
    // `print_r` is what somebody reaches for at a breakpoint, and it honours
    // `__debugInfo`, so it prints the placeholder rather than the secret.
    $credential = Credential::of(WHAT_THE_STACK_GAVE);

    expect(print_r($credential, return: true))->not->toContain(WHAT_THE_STACK_GAVE)
        ->and(print_r($credential, return: true))->toContain('a credential, hidden');
});

it('N1-R15 — is still readable by var_export, which is the honest limit', function (): void {
    // `var_export` reads private properties directly and honours nothing — not
    // `__debugInfo`, not `__serialize`. Asserted as it actually behaves rather
    // than as one would wish, because a test claiming otherwise would be a
    // guarantee this type does not give.
    //
    // What closes it is `Diagnostics`, which has no parameter a `Credential`
    // fits through, so no report can be assembled that walks one. `Session`
    // carries the same note for the same reason.
    expect(var_export(Credential::of(WHAT_THE_STACK_GAVE), return: true))
        ->toContain(WHAT_THE_STACK_GAVE);
});

it('says at a breakpoint which of the two states it is in', function (): void {
    // The one question that can be answered without printing the secret, and
    // the one somebody actually has when they stop here.
    $credential = Credential::of(WHAT_THE_STACK_GAVE);
    $credential->forTheExchange();

    expect(print_r($credential, return: true))->toContain('(spent)');
});
