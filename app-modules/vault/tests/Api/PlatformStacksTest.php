<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;
use Modules\Vault\Api\PlatformStacks;

use function str_repeat;

use Tests\Support\Fakes\APlatformStore;

/**
 * What the contract deliberately leaves out.
 *
 * The contract asserts only what the adapter and the fake must both promise,
 * which cannot include anything about a stored record — the fake stores
 * nothing. Reading one back is this adapter's whole job, and the rules about a
 * shape number and an unrecognised shape are about exactly that, so they are
 * driven here.
 */
const UNDER = 'lemonfiber.stacks';

function aStack(string $called = 'The loft', string $seed = 'a', string $at = 'https://192.168.1.42'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of($at),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** A store already holding whatever a previous launch is supposed to have written. */
function holding(string $written): APlatformStore
{
    $store = APlatformStore::working();
    $store->alreadyHolding(UNDER, $written);

    return $store;
}

/** A record in the shape this build writes, with the parts a test wants to break. */
function recorded(mixed $shape = 1, mixed $stacks = null): string
{
    return (string) json_encode([
        'shape' => $shape,
        'stacks' => $stacks ?? [[
            'id' => str_repeat('a', Nonce::SHORTEST),
            'name' => 'The loft',
            'address' => 'https://192.168.1.42',
            'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
        ]],
    ]);
}

it('reads back what a previous launch wrote', function (): void {
    $store = APlatformStore::working();
    new PlatformStacks($store)->remember(aStack());

    $read = new PlatformStacks($store)->configured();

    expect($read->stack(aStack()->id())->name()->shown())->toBe('The loft');
});

it('N1-R15 — writes the address down deliberately, rather than by serialising the type', function (): void {
    // `Address::jsonSerialize()` answers with a placeholder on purpose, so a
    // record built by handing the value to `json_encode` would store the words
    // "a stack address, hidden" and be unreadable on the next launch — with
    // nothing failing until then.
    $store = APlatformStore::working();
    new PlatformStacks($store)->remember(aStack(at: 'https://192.168.1.77'));

    expect($store->whatIsUnder(UNDER))->toContain('192.168.1.77');
});

it('N1-R32 — carries the version of the shape it was written in', function (): void {
    $store = APlatformStore::working();
    new PlatformStacks($store)->remember(aStack());

    $written = json_decode($store->whatIsUnder(UNDER) ?? '', associative: true);

    expect($written)->toBeArray()->toHaveKey('shape');
});

it('N1-R33 — discards a record written in a shape it does not know', function (): void {
    // Never interpreted as though it were current. Reading the parts that
    // happen to parse would assemble a stack list out of something nobody
    // wrote, which is an app offering to operate a machine it cannot name.
    $stacks = new PlatformStacks(holding(recorded(shape: 2)));

    expect($stacks->configured()->isEmpty())->toBeTrue();
});

it('discards a record whose shape is not a number at all', function (): void {
    expect(new PlatformStacks(holding(recorded(shape: 'one')))->configured()->isEmpty())->toBeTrue();
});

it('discards a record with no shape on it', function (): void {
    $written = (string) json_encode(['stacks' => []]);

    expect(new PlatformStacks(holding($written))->configured()->isEmpty())->toBeTrue();
});

it('discards a record that is not a record', function (): void {
    foreach (['', 'not json at all', '"a string"', '42'] as $rubbish) {
        expect(new PlatformStacks(holding($rubbish))->configured()->isEmpty())
            ->toBeTrue($rubbish);
    }
});

it('discards a record whose stacks are missing or are not a list', function (): void {
    $noStacks = (string) json_encode(['shape' => 1]);
    $keyed = recorded(stacks: ['first' => ['id' => 'a']]);

    expect(new PlatformStacks(holding($noStacks))->configured()->isEmpty())->toBeTrue()
        ->and(new PlatformStacks(holding($keyed))->configured()->isEmpty())->toBeTrue();
});

it('reads a record holding no stacks as a device paired with nothing', function (): void {
    expect(new PlatformStacks(holding(recorded(stacks: [])))->configured())
        ->toEqual(Configured::none());
});

it('refuses the whole record where one row is not a stack', function (): void {
    // All or nothing. A half-read list shows an operator some of their machines
    // with no sign that the rest are missing, and the missing one is the stack
    // they are looking for often enough to matter.
    $good = [
        'id' => str_repeat('a', Nonce::SHORTEST),
        'name' => 'The loft',
        'address' => 'https://192.168.1.42',
        'fingerprint' => str_repeat('a', Fingerprint::CHARACTERS),
    ];

    $broken = [
        'a row that is not an array' => 'nonsense',
        'a name the type refuses' => ['name' => '  '] + $good,
        'a digest of the wrong length' => ['fingerprint' => 'too short'] + $good,
        'an address with no scheme' => ['address' => '192.168.1.42'] + $good,
        'an identifier that is blank' => ['id' => ''] + $good,
        'a part that is not text' => ['name' => 42] + $good,
        'a part that is not there' => ['id' => $good['id'], 'name' => $good['name']],
    ];

    foreach ($broken as $why => $row) {
        expect(new PlatformStacks(holding(recorded(stacks: [$good, $row])))->configured()->isEmpty())
            ->toBeTrue($why);
    }
});

it('reads a device whose store has never held this record as paired with nothing', function (): void {
    expect(new PlatformStacks(APlatformStore::working())->configured()->isEmpty())->toBeTrue();
});

it('reads a device with no store at all as paired with nothing', function (): void {
    // The conservative direction, and the one that lands on the no-stacks screen:
    // treating unreadable state as though some unknown stack were configured
    // would be an app offering to operate a machine it cannot name.
    expect(new PlatformStacks(APlatformStore::absent())->configured()->isEmpty())->toBeTrue();
});

/**
 * Why a stack was not written down, as a word a test can compare.
 *
 * Named for this file: the vault's module suites share one namespace, and two
 * functions of the same name are a fatal the moment both load (`G10`).
 */
function whyItWasRefused(Remembered $remembered): string
{
    return $remembered->either(
        remembered: static fn(): Code => Code::of('safely'),
        refused: static fn(WhyAStackCannotBeRemembered $why): Code => Code::of($why->value),
    )->shown();
}

it('holds nothing where the store has no record under that key', function (): void {
    // The first launch of an app on a phone that has never been paired. The
    // lock has nothing to stand in front of, and says so.
    expect(new PlatformStacks(APlatformStore::working())->holdsAny())->toBeFalse();
});

it('refuses a stack whose name cannot be written down', function (): void {
    // A name is whatever somebody typed or pasted, and `StackName` asks only
    // that it not be blank — so a byte sequence that is not text reaches here
    // and `json_encode` answers false. The remedy offered is *try again*, which
    // is the right one: there is nothing wrong with the device.
    $refused = new PlatformStacks(APlatformStore::working())->remember(
        Stack::of(
            StackId::rememberedAs('the-loft'),
            StackName::of("a name with a broken byte \xB1\x31 in it"),
            Address::of('https://192.168.1.42'),
            Fingerprint::of(str_repeat('a', 64)),
        ),
    );

    expect(whyItWasRefused($refused))->toBe('store_would_not_open');
});

it('holds nothing where every pairing has been forgotten', function (): void {
    // The store keeps the key and writes an empty list into it, so a device
    // that has been unpaired answers *found* with something in it. A lock
    // reading the status alone would engage in front of nothing.
    expect(new PlatformStacks(holding('[]'))->holdsAny())->toBeFalse();
});
