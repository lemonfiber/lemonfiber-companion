<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AddressIsUnreachable;

use function str_contains;

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
    // N1-R15 names a stack address beside a credential, and an exception
    // message is the easiest of the three to forget.
    $said = '';

    try {
        Address::of('stack.local');
    } catch (AddressIsUnreachable $refusal) {
        $said = $refusal->getMessage();
    }

    expect(str_contains($said, 'stack.local'))->toBeFalse();
});

it('answers N1-R12 from the one fact that decides it', function (): void {
    expect(Address::of(AN_ADDRESS)->isEncrypted())->toBeTrue();
    expect(Address::of('HTTPS://stack.local')->isEncrypted())->toBeTrue();
    expect(Address::of('http://192.168.1.42')->isEncrypted())->toBeFalse();
});

it('takes a plain address rather than refusing it', function (): void {
    // A stack on a local network may genuinely be reached over http. Refusing
    // would tell an operator their pairing code is broken, when what is true is
    // that their connection is not private — which is the distinction N1-R12
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
