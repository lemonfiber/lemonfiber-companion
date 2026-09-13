<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsNotConfigured;
use Modules\Kernel\Api\StackName;

use function str_repeat;

/**
 * A stack, identified by a nonce long enough to be one.
 *
 * The identifier is minted the way pairing mints it rather than handed in as a
 * string, so nothing here can hold an identity the application could not.
 */
function stack(string $seed, string $called = 'The loft', string $at = 'https://192.168.1.42'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of($at),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** @return list<string> */
function namesIn(Configured $record): array
{
    return array_map(
        static fn(Stack $held): string => $held->name()->shown(),
        iterator_to_array($record, preserve_keys: false),
    );
}

it('N1-R35 — a device introduced to nothing says so', function (): void {
    expect(Configured::none()->isEmpty())->toBeTrue();
});

it('stops being empty once it holds a stack', function (): void {
    expect(Configured::none()->with(stack('a'))->isEmpty())->toBeFalse();
});

it('N1-R11 — holds more than one stack at once', function (): void {
    $record = Configured::none()->with(stack('a', 'The loft'))->with(stack('b', 'My mum\'s'));

    expect(namesIn($record))->toBe(['The loft', 'My mum\'s']);
});

it('answers with the stack that was named', function (): void {
    $loft = stack('a', 'The loft');
    $record = Configured::none()->with(stack('b', 'My mum\'s'))->with($loft);

    expect($record->stack($loft->id())->name()->shown())->toBe('The loft');
});

it('N1-R11 — refuses a stack it does not hold rather than substituting one', function (): void {
    // The whole clause: a lookup that misses and falls back to the first, the
    // only, or whatever was there before is how a reading comes to be
    // attributed to the wrong machine. There is exactly one stack held here,
    // which is the case a fallback would look correct in.
    $record = Configured::none()->with(stack('a'));

    expect(fn(): Stack => $record->stack(stack('b')->id()))
        ->toThrow(StackIsNotConfigured::class);
});

it('names the stack it could not find, so the refusal says which', function (): void {
    $unheld = stack('b');

    expect(fn(): Stack => Configured::none()->stack($unheld->id()))
        ->toThrow(StackIsNotConfigured::class, $unheld->id()->stored());
});

it('says whether it has been introduced to a stack, without raising', function (): void {
    $loft = stack('a');
    $record = Configured::none()->with($loft);

    expect($record->knows($loft->id()))->toBeTrue()
        ->and($record->knows(stack('b')->id()))->toBeFalse();
});

it('N1-R22 — a stack that comes back on another address is the same stack', function (): void {
    // Re-pairing, which is what N1-R20 offers as the remedy for a changed
    // certificate. A second entry here would be a device holding one machine
    // twice, and the operator choosing between two rows that are the same
    // house.
    $record = Configured::none()
        ->with(stack('a', 'The loft', 'https://192.168.1.42'))
        ->with(stack('a', 'The loft', 'https://192.168.1.77'));

    expect(namesIn($record))->toHaveCount(1)
        ->and($record->stack(stack('a')->id())->at()->forTheClient())
        ->toContain('192.168.1.77');
});

it('keeps a re-paired stack in its place rather than moving it to the end', function (): void {
    // Order is how the operator recognises their own list. A list that
    // rearranges itself after a re-pair reads as something having gone wrong,
    // which is the opposite of what a successful re-pair should look like.
    $record = Configured::none()
        ->with(stack('a', 'The loft'))
        ->with(stack('b', 'My mum\'s'))
        ->with(stack('a', 'The attic'));

    expect(namesIn($record))->toBe(['The attic', 'My mum\'s']);
});

it('is immutable, so a screen holding one cannot be changed by another', function (): void {
    $held = Configured::none()->with(stack('a'));

    $held->with(stack('b'));

    expect(namesIn($held))->toHaveCount(1);
});

it('reads a retained list back, collapsing an identifier written twice', function (): void {
    // Two entries for one identifier is a corrupt retained list, and they are
    // the same stack by the only definition of sameness there is. Collapsing to
    // the later one is the reading that cannot attribute anything to the wrong
    // machine.
    $record = Configured::of(
        stack('a', 'The loft'),
        stack('b', 'My mum\'s'),
        stack('a', 'The attic'),
    );

    expect(namesIn($record))->toBe(['The attic', 'My mum\'s']);
});

it('reads an empty retained list as a device introduced to nothing', function (): void {
    expect(Configured::of()->isEmpty())->toBeTrue();
});
