<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\StackId;
use Modules\Vault\Api\PlatformVerdicts;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;

/**
 * What the contract deliberately leaves out.
 *
 * The contract asserts only what this adapter and the fake must both promise,
 * which cannot include anything about a stored record — the fake stores
 * nothing. Reading one back is this adapter's whole job, and the rules about a
 * shape number and an unrecognised shape are about exactly that, so they are
 * driven here.
 *
 * Every refusal below answers *nothing held* rather than raising. A launch is
 * not a place to throw, and the cost of discarding a record this build cannot
 * read is one round trip: the opening screen shows the stack with no verdict
 * yet, which is a state it already draws, and the first ask refills it.
 */
const VERDICTS_UNDER = 'lemonfiber.verdicts';

function aStackWithAVerdict(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** What the adapter answers for a stack, flattened to one string. */
function whatItHolds(PlatformVerdicts $verdicts, StackId $stack): string
{
    return $verdicts->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(): Code => Code::of('live-which-cannot-happen'),
            retained: static fn(object $overall, Instant $at): Code => Code::of(sprintf(
                '%s|%d',
                $overall instanceof Overall ? $overall->value : 'not-a-verdict',
                $at->epochSeconds(),
            )),
        ),
    )->shown();
}

/** A store already holding a record, written as the argument says. */
function aStoreHolding(mixed $record): APlatformStore
{
    $store = APlatformStore::working();
    $store->alreadyHolding(VERDICTS_UNDER, (string) json_encode($record));

    return $store;
}

it('N1-R32 — writes the shape it reads, so a record names the build that made it', function (): void {
    $store = APlatformStore::working();
    $verdicts = new PlatformVerdicts($store);

    $verdicts->remember(aStackWithAVerdict(), Overall::Degraded, Instant::atEpochSeconds(1_770_000_000));

    expect($store->whatIsUnder(VERDICTS_UNDER))->toContain('"shape":1');
});

it('N1-R33 — discards a record written in a shape this build does not know', function (): void {
    // Never interpreted as though it were current. A record from a newer build
    // read optimistically would open the app on a verdict assembled from
    // something nobody in this build wrote.
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 99,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => ['overall' => 'broken', 'at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('discards a record that is not a record at all', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding('not a record'));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('discards a record of the right shape with no verdicts in it', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding(['shape' => 1]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('discards a record whose verdicts are not verdicts', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding(['shape' => 1, 'verdicts' => 'all of them']));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing for a stack whose row is not a row', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 1,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => 'broken'],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing where the row names a word this build does not read', function (): void {
    // Discarding an unrecognised shape, at the size of one field. A verdict this
    // app cannot read is not
    // guessed at: the alternative is opening the operator on a word chosen by
    // whichever arm happened to be first.
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 1,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => ['overall' => 'on fire', 'at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing where the row says no word at all', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 1,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => ['at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing where the row says when in something that is not a count', function (): void {
    // A retained reading must carry its age, which is why this is a refusal
    // rather than a default: a verdict
    // whose age cannot be read would be shown with an age this app made up,
    // which is the one thing the requirement exists to prevent.
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 1,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => ['overall' => 'broken', 'at' => 'this morning']],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing where the row never says when', function (): void {
    $verdicts = new PlatformVerdicts(aStoreHolding([
        'shape' => 1,
        'verdicts' => ['aaaaaaaaaaaaaaaa' => ['overall' => 'broken']],
    ]));

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('holds nothing before anything has ever been written', function (): void {
    $verdicts = new PlatformVerdicts(APlatformStore::absent());

    expect(whatItHolds($verdicts, aStackWithAVerdict()))->toBe('nothing-held');
});

it('says so where the device will not keep it, and asks nothing of anybody', function (): void {
    // The quietest outcome in this app. The screen that asked is holding the
    // live answer and is showing it; the only consequence is that the next
    // opening has nothing to open on, which is a state it already draws.
    $verdicts = new PlatformVerdicts(APlatformStore::refusing());

    $went = $verdicts->remember(
        aStackWithAVerdict(),
        Overall::Broken,
        Instant::atEpochSeconds(1_770_000_000),
    )->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down-at-%d', $at->epochSeconds())),
        notKept: static fn(): Code => Code::of('not-kept'),
    );

    expect($went->shown())->toBe('not-kept');
});

it('keeps a second stack without losing the first, across a write', function (): void {
    // The record is one key, so remembering folds into what is there rather
    // than replacing it. A write that dropped the others would lose every
    // stack's verdict but the one just asked.
    $store = APlatformStore::working();
    $verdicts = new PlatformVerdicts($store);

    $verdicts->remember(aStackWithAVerdict('a'), Overall::Broken, Instant::atEpochSeconds(1_770_000_000));
    $verdicts->remember(aStackWithAVerdict('b'), Overall::Healthy, Instant::atEpochSeconds(1_770_000_060));

    expect(whatItHolds($verdicts, aStackWithAVerdict('a')))->toBe('broken|1770000000')
        ->and(whatItHolds($verdicts, aStackWithAVerdict('b')))->toBe('healthy|1770000060');
});

it('reads back what an earlier session wrote, which is the point of the store', function (): void {
    // The clause the contract cannot assert, because the fake does not survive
    // a launch. A second adapter over the same store is the nearest thing to
    // tomorrow that a test can arrange.
    $store = APlatformStore::working();

    new PlatformVerdicts($store)->remember(
        aStackWithAVerdict(),
        Overall::Degraded,
        Instant::atEpochSeconds(1_770_000_000),
    );

    expect(whatItHolds(new PlatformVerdicts($store), aStackWithAVerdict()))->toBe('degraded|1770000000');
});

it('answers the moment it wrote down, so a caller can say when it was kept', function (): void {
    $went = new PlatformVerdicts(APlatformStore::working())->remember(
        aStackWithAVerdict(),
        Overall::Healthy,
        Instant::atEpochSeconds(1_770_000_000),
    );

    expect($went)->toBeInstanceOf(Noted::class)
        ->and($went->either(
            down: static fn(Instant $at): Code => Code::of(sprintf('%d', $at->epochSeconds())),
            notKept: static fn(): Code => Code::of('not-kept'),
        )->shown())->toBe('1770000000');
});
