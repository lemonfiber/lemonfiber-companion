<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\StackId;
use Modules\Vault\Api\PlatformStandings;

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
 * read is one row's word: the list shows the stack as not known yet, which is
 * a state it already draws, until the stack's screen hears the word again.
 */
const STANDINGS_UNDER = 'lemonfiber.standings';

function aStackWithAWord(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** What the adapter answers for a stack, flattened to one string. */
function whatItHolds(PlatformStandings $standings, StackId $stack): string
{
    return $standings->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(): Code => Code::of('live-which-cannot-happen'),
            retained: static fn(object $standing, Instant $at): Code => Code::of(sprintf(
                '%s|%d',
                $standing instanceof HowItStands ? $standing->value : 'not-a-word',
                $at->epochSeconds(),
            )),
        ),
    )->shown();
}

/** A store already holding a record, written as the argument says. */
function aStoreHolding(mixed $record): APlatformStore
{
    $store = APlatformStore::working();
    $store->alreadyHolding(STANDINGS_UNDER, (string) json_encode($record));

    return $store;
}

it('writes the shape it reads, so a record names the build that made it', function (): void {
    $store = APlatformStore::working();
    $standings = new PlatformStandings($store);

    $standings->remember(aStackWithAWord(), HowItStands::Degraded, Instant::atEpochSeconds(1_770_000_000));

    expect($store->whatIsUnder(STANDINGS_UNDER))->toContain('"shape":1');
});

it('discards a record written in a shape this build does not know', function (): void {
    // Never interpreted as though it were current. A record from a newer build
    // read optimistically would open the app on a word assembled from
    // something nobody in this build wrote.
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 99,
        'standings' => ['aaaaaaaaaaaaaaaa' => ['standing' => 'broken', 'at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('discards a record that is not a record at all', function (): void {
    $standings = new PlatformStandings(aStoreHolding('not a record'));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('discards a record of the right shape with no words in it', function (): void {
    $standings = new PlatformStandings(aStoreHolding(['shape' => 1]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('discards a record whose words are not rows', function (): void {
    $standings = new PlatformStandings(aStoreHolding(['shape' => 1, 'standings' => 'all of them']));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing for a stack whose row is not a row', function (): void {
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 1,
        'standings' => ['aaaaaaaaaaaaaaaa' => 'broken'],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing where the row names a word this build does not read', function (): void {
    // Discarding an unrecognised shape, at the size of one field. A word this
    // app cannot read is not guessed at: the alternative is opening the
    // operator on a word chosen by whichever arm happened to be first.
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 1,
        'standings' => ['aaaaaaaaaaaaaaaa' => ['standing' => 'on fire', 'at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing where the row says no word at all', function (): void {
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 1,
        'standings' => ['aaaaaaaaaaaaaaaa' => ['at' => 1_770_000_000]],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing where the row says when in something that is not a count', function (): void {
    // A retained reading must carry its age, which is why this is a refusal
    // rather than a default: a word whose age cannot be read would be shown
    // with an age this app made up.
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 1,
        'standings' => ['aaaaaaaaaaaaaaaa' => ['standing' => 'broken', 'at' => 'this morning']],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing where the row never says when', function (): void {
    $standings = new PlatformStandings(aStoreHolding([
        'shape' => 1,
        'standings' => ['aaaaaaaaaaaaaaaa' => ['standing' => 'broken']],
    ]));

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('holds nothing before anything has ever been written', function (): void {
    $standings = new PlatformStandings(APlatformStore::absent());

    expect(whatItHolds($standings, aStackWithAWord()))->toBe('nothing-held');
});

it('says so where the device will not keep it, and asks nothing of anybody', function (): void {
    // The quietest outcome in this app. The screen that heard it is showing
    // it; the only consequence is that the list has nothing to say for that
    // stack, which is a state it already draws.
    $standings = new PlatformStandings(APlatformStore::refusing());

    $went = $standings->remember(
        aStackWithAWord(),
        HowItStands::Broken,
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
    // stack's word but the one just heard.
    $store = APlatformStore::working();
    $standings = new PlatformStandings($store);

    $standings->remember(aStackWithAWord('a'), HowItStands::Broken, Instant::atEpochSeconds(1_770_000_000));
    $standings->remember(aStackWithAWord('b'), HowItStands::Healthy, Instant::atEpochSeconds(1_770_000_060));

    expect(whatItHolds($standings, aStackWithAWord('a')))->toBe('broken|1770000000')
        ->and(whatItHolds($standings, aStackWithAWord('b')))->toBe('healthy|1770000060');
});

it('reads back what an earlier session wrote, which is the point of the store', function (): void {
    // The clause the contract cannot assert, because the fake does not survive
    // a launch. A second adapter over the same store is the nearest thing to
    // tomorrow that a test can arrange.
    $store = APlatformStore::working();

    new PlatformStandings($store)->remember(
        aStackWithAWord(),
        HowItStands::Degraded,
        Instant::atEpochSeconds(1_770_000_000),
    );

    expect(whatItHolds(new PlatformStandings($store), aStackWithAWord()))->toBe('degraded|1770000000');
});

it('answers the moment it wrote down, so a caller can say when it was kept', function (): void {
    $went = new PlatformStandings(APlatformStore::working())->remember(
        aStackWithAWord(),
        HowItStands::Healthy,
        Instant::atEpochSeconds(1_770_000_000),
    );

    expect($went)->toBeInstanceOf(Noted::class)
        ->and($went->either(
            down: static fn(Instant $at): Code => Code::of(sprintf('%d', $at->epochSeconds())),
            notKept: static fn(): Code => Code::of('not-kept'),
        )->shown())->toBe('1770000000');
});

/**
 * Whether a word was written down, as a word a test can compare.
 *
 * Named for this file: the vault's module suites share one namespace (`G10`).
 */
function howItWentDown(Noted $noted): string
{
    return $noted->either(
        down: static fn(): Code => Code::of('down'),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('keeps no word whose record cannot be written down', function (): void {
    // The stack's identifier becomes the key of the row, and `StackId` asks
    // only that it not be blank — so a byte sequence that is not text reaches
    // `json_encode`, which answers false. Nothing is kept, and that costs the
    // list one row's word until the stack's screen hears it again.
    $went = new PlatformStandings(APlatformStore::working())->remember(
        StackId::rememberedAs("a stack with a broken byte \xB1\x31 in it"),
        HowItStands::Healthy,
        Instant::atEpochSeconds(1_700_000_000),
    );

    expect(howItWentDown($went))->toBe('not-kept');
});
