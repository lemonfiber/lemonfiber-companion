<?php

declare(strict_types=1);

use Lemonfiber\Native\Names;
use Native\Mobile\Testing\FakeBridge;

// The look-up's PHP face, driven through the real bridge call.
//
// The bridge name is written out as a literal, for `LinkTest`'s reason.

beforeEach(function (): void {
    FakeBridge::disable();
});

it('answers the addresses the phone found, in the order it gave them', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Resolve', ['outcome' => 'found', 'addresses' => ['192.168.1.42', '2001:db8::1']]);

    expect(new Names()->addressesOf('den.local'))->toBe(['192.168.1.42', '2001:db8::1']);

    $bridge->assertCalled('Lemonfiber.Resolve', static fn(mixed $params): bool => $params === ['host' => 'den.local']);
});

it('answers nothing where the phone found nothing', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Resolve', ['outcome' => 'nothing', 'addresses' => []]);

    expect(new Names()->addressesOf('den.local'))->toBe([]);
});

it('keeps only what is an address', function (): void {
    // A shim from another build, or one that went wrong: what is not an
    // address is never sent to.
    FakeBridge::enable()->respondTo('Lemonfiber.Resolve', ['outcome' => 'found', 'addresses' => ['192.168.1.42', 'den.local', 7, '', '10.0.0.7']]);

    expect(new Names()->addressesOf('den.local'))->toBe(['192.168.1.42', '10.0.0.7']);
});

it('reads no answer, or one it does not know, as nothing found', function (array $answered): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Resolve', $answered);

    expect(new Names()->addressesOf('den.local'))->toBe([]);
})->with([
    'a word it does not know' => [['outcome' => 'probably', 'addresses' => ['192.168.1.42']]],
    'found, with no list' => [['outcome' => 'found']],
    'found, with something else where the list goes' => [['outcome' => 'found', 'addresses' => '192.168.1.42']],
    'no envelope' => [['nothing_useful' => true]],
]);

it('reads no bridge at all as nothing found', function (): void {
    FakeBridge::enable();

    expect(new Names()->addressesOf('den.local'))->toBe([]);
});
