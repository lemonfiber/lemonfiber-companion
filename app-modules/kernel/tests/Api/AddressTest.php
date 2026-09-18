<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;
use function mb_strlen;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AddressIsUnreachable;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\Scheme;

use function print_r;
use function serialize;
use function sprintf;
use function str_contains;
use function unserialize;
use function var_export;

const AN_ADDRESS = 'https://192.168.1.42:8443';

it('carries what pairing material gave it', function (): void {
    expect(Address::of(AN_ADDRESS)->forTheClient())->toBe(AN_ADDRESS);
    expect(Address::of(' https://stack.local ')->forTheClient())->toBe('https://stack.local');
});

it('refuses an address with nothing in it', function (): void {
    expect(fn(): Address => Address::of('   '))->toThrow(AddressIsUnreachable::class, 'nowhere to reach');
});

it('refuses an address that cannot be dialled, at the pairing rather than the request', function (): void {
    // The two read completely differently to an operator: "that pairing code is
    // not right" is something they can act on, and "the stack did not answer"
    // sends them to look at a machine nothing ever contacted.
    expect(fn(): Address => Address::of('192.168.1.42:8443'))
        ->toThrow(AddressIsUnreachable::class, 'needs a scheme');
});

it('says nothing about the address when it refuses one', function (): void {
    // A stack address is named beside a credential, and an exception
    // message is the easiest of the three to forget.
    $said = '';

    try {
        Address::of('stack.local');
    } catch (AddressIsUnreachable $refusal) {
        $said = $refusal->getMessage();
    }

    expect(str_contains($said, 'stack.local'))->toBeFalse();

    // And the same of the refusal that does quote a vocabulary back: it lists
    // what a stack is reached over, never what this one said it was.
    try {
        Address::of('ftp://stack.local');
    } catch (AddressIsUnreachable $refusal) {
        $said = $refusal->getMessage();
    }

    expect(str_contains($said, 'ftp'))->toBeFalse();
});

it('refuses a scheme a stack is not dialled over', function (): void {
    // Not the same refusal as "no scheme". `file:///etc/passwd` parses, carries
    // a scheme, and would be answered `isEncrypted() === false` — a true
    // sentence about a stack that was never there. The honest reading is that
    // the pairing material did not survive the trip.
    expect(fn(): Address => Address::of('file:///etc/passwd'))
        ->toThrow(AddressIsUnreachable::class, 'something else');

    expect(fn(): Address => Address::of('ftp://stack.local'))
        ->toThrow(AddressIsUnreachable::class, 'something else');
});

it('lists the ways a stack is dialled by reading them, not by spelling them', function (): void {
    // The refusal tells an operator what a stack *is* reached over, so the
    // sentence carries the vocabulary. Read from the enum rather than typed
    // beside it: a scheme added there and not here would leave somebody being
    // told their address is impossible by a sentence that no longer lists the
    // way they were told to reach their stack.
    $said = '';

    try {
        Address::of('ftp://stack.local');
    } catch (AddressIsUnreachable $refusal) {
        $said = $refusal->getMessage();
    }

    foreach (Scheme::cases() as $scheme) {
        expect(str_contains($said, $scheme->value))->toBeTrue();
    }
});

it('answers N1-R12 from the one fact that decides it', function (): void {
    expect(Address::of(AN_ADDRESS)->isEncrypted())->toBeTrue();
    expect(Address::of('HTTPS://stack.local')->isEncrypted())->toBeTrue();
    expect(Address::of('http://192.168.1.42')->isEncrypted())->toBeFalse();
});

it('takes a plain address rather than refusing it', function (): void {
    // A stack on a local network may genuinely be reached over http. Refusing
    // would tell an operator their pairing code is broken, when what is true is
    // that their connection is not private — which is the distinction the rule
    // exists to keep, and it is lost if the value cannot be constructed.
    expect(Address::of('http://192.168.1.42')->forTheClient())->toBe('http://192.168.1.42');
});

it('is the same address, and is not another', function (): void {
    expect(Address::of(AN_ADDRESS)->is(Address::of(AN_ADDRESS)))->toBeTrue();
    expect(Address::of(AN_ADDRESS)->is(Address::of('https://stack.local')))->toBeFalse();
});

it('does not print or serialise itself', function (): void {
    // Both halves asserted: that the address is gone, and that something is
    // there in its place. A redaction that returns nothing reads in a debugger
    // as an object with no properties, which is indistinguishable from an
    // address that was never set.
    expect(Address::of(AN_ADDRESS)->__debugInfo())->toBe(['url' => '(a stack address, hidden)']);
    expect(json_encode(['at' => Address::of(AN_ADDRESS)]))->toBe('{"at":"(a stack address, hidden)"}');
});

it('refuses a malformed address as well as one with no scheme', function (): void {
    // `parse_url` answers false rather than null for this one, which is the
    // other half of the `is_string` check.
    expect(fn(): Address => Address::of(':80'))
        ->toThrow(AddressIsUnreachable::class, 'needs a scheme');
});

it('is closed to every reader that asks the type, and open to the one that does not', function (): void {
    // The docblock says this carries the redaction `Session` carries, so it
    // carries the same caveat with it. `var_export` walks private properties
    // directly and no method intercepts it.
    //
    // Worth stating twice rather than once and cross-referenced, because the
    // reason differs: a session is a credential, and an address is where
    // somebody lives. A report assembler has to refuse to walk both, and
    // a reader who found the caveat only on the credential could reasonably
    // conclude the other was already sealed.
    expect(str_contains(print_r(Address::of(AN_ADDRESS), return: true), AN_ADDRESS))->toBeFalse();
    expect(str_contains(var_export(Address::of(AN_ADDRESS), return: true), AN_ADDRESS))->toBeTrue();
});

it('N1-R15 — an address does not leave the process in a serialised payload', function (): void {
    // Not secrecy. Anything that serialises stack addresses accumulates a map
    // of private networks, which is the thing actually being protected.
    expect(fn(): string => serialize(Address::of(AN_ADDRESS)))
        ->toThrow(MustNotLeaveThisProcess::class, 'may not be serialised');
});

it('N1-R15 — an address does not come back from a serialised payload either', function (): void {
    // Same door, other side. Without `__unserialize` a crafted payload naming this
    // class would be walked back into an object with whatever properties it carried.
    //
    // Built from the class name rather than typed as a literal: a renamed class
    // would leave a hand-written payload naming a type that no longer exists,
    // and `unserialize` would answer `false` quietly while this test went on
    // passing for the wrong reason.
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(Address::class), Address::class);

    expect(fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class, 'may not be serialised');
});
