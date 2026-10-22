<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\AnEventHeard;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Vault\Api\PlatformStacks;
use Modules\Vault\Api\PlatformWhereHearingLeftOff;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;
use Tests\Support\WhereItResumes;

/**
 * What the contract leaves out: the value written, and reading it back after a launch.
 *
 * Every value this build did not write reads as nothing kept, so the stream
 * opens from the start and hands back no past alert.
 */
function aStackWhoseStreamLeftOff(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** The key where a stack's stream left off is kept under. */
function whereHearingIsKeptFor(StackId $stack): string
{
    return sprintf('lemonfiber.heard-to.%s', $stack->stored());
}

/** What the adapter over a store already holding this value finds for the stack. */
function whereAStreamResumesAfter(mixed $value): string
{
    $store = APlatformStore::working()->alreadyHolding(whereHearingIsKeptFor(aStackWhoseStreamLeftOff()), (string) json_encode($value));

    return WhereItResumes::said(new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->leftOffAt(aStackWhoseStreamLeftOff()));
}

it('writes the event with the shape it reads, under a key for the stack', function (): void {
    $store = APlatformStore::working();

    new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->heardTo(aStackWhoseStreamLeftOff(), AnEventHeard::named('run-41'));

    expect($store->keysHeld())->toBe([whereHearingIsKeptFor(aStackWhoseStreamLeftOff())])
        ->and($store->whatIsUnder(whereHearingIsKeptFor(aStackWhoseStreamLeftOff())))->toBe('{"shape":1,"after":"run-41"}');
});

it('reads back what an earlier launch wrote, which is the point of the store', function (): void {
    $store = APlatformStore::working();

    new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->heardTo(aStackWhoseStreamLeftOff(), AnEventHeard::named('run-41'));

    expect(WhereItResumes::said(new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->leftOffAt(aStackWhoseStreamLeftOff())))->toBe('after:run-41');
});

it('reads a value it did not write as from the start', function (mixed $value): void {
    expect(whereAStreamResumesAfter($value))->toBe('from-the-start');
})->with([
    'a newer shape' => [['shape' => 2, 'after' => 'run-41']],
    'no shape' => [['after' => 'run-41']],
    'no event' => [['shape' => 1]],
    'an event that is not text' => [['shape' => 1, 'after' => 41]],
    'a blank event' => [['shape' => 1, 'after' => '']],
    'text' => ['run-41'],
]);

it('reads a value that is not JSON as from the start', function (): void {
    $store = APlatformStore::working()->alreadyHolding(whereHearingIsKeptFor(aStackWhoseStreamLeftOff()), 'run-41');

    expect(WhereItResumes::said(new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->leftOffAt(aStackWhoseStreamLeftOff())))->toBe('from-the-start');
});

it('says a store it cannot read may still keep something of a stack', function (): void {
    expect(new PlatformWhereHearingLeftOff(APlatformStore::refusing(), new PlatformStacks(APlatformStore::refusing()))->keepsAnythingOf(aStackWhoseStreamLeftOff()))->toBeTrue();
});

it('keeps no event that cannot be written down', function (): void {
    $store = APlatformStore::working();

    expect(new PlatformWhereHearingLeftOff($store, new PlatformStacks($store))->heardTo(aStackWhoseStreamLeftOff(), AnEventHeard::named("run-\xB1\x31")))->toBeFalse()
        ->and($store->keysHeld())->toBe([]);
});
