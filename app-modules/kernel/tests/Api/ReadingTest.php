<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;

use function sprintf;

/** What a reading holds, as a named type rather than a stub. */
function readValue(): Code
{
    return Code::of('STACK-7');
}

/**
 * Both branches, each naming itself and what it was handed.
 *
 * Written once rather than per test so that every case reads the same fold,
 * and so that neither arm can quietly stop using what it was given — an arm
 * that ignores its arguments would still pass a test that only asked which
 * side ran.
 */
function foldReading(Reading $reading): Code
{
    return $reading->either(
        live: static fn(object $value): Code => Code::of(sprintf('live:%s', $value::class)),
        retained: static fn(object $value, Instant $readAt): Code => Code::of(sprintf(
            'retained:%s:%d',
            $value::class,
            $readAt->epochSeconds(),
        )),
    );
}

it('takes the live branch when it was read just now', function (): void {
    expect(foldReading(Reading::live(readValue()))->shown())->toBe(sprintf('live:%s', Code::class));
});

it('hands the retained arm the value and when it was read, together', function (): void {
    // The point of the type. A screen holding the value has been handed the
    // age in the same call, so rendering it without one is a decision somebody
    // made rather than a call they forgot (N1-R9).
    expect(foldReading(Reading::retained(readValue(), Instant::atEpochSeconds(1_700_000_000)))->shown())
        ->toBe(sprintf('retained:%s:1700000000', Code::class));
});

it('hands the live arm the value itself', function (): void {
    $value = readValue();

    $answer = Reading::live($value)->either(
        live: static fn(object $given): object => $given,
        retained: static fn(object $given): Code => Code::of(sprintf('retained:%s', $given::class)),
    );

    // Identity rather than equality: a fold that rebuilt the value would
    // satisfy a comparison and lose whatever the caller was holding.
    expect($answer)->toBe($value);
});

it('hands the retained arm the value itself', function (): void {
    $value = readValue();

    $answer = Reading::retained($value, Instant::atEpochSeconds(1))->either(
        live: static fn(object $given): Code => Code::of(sprintf('live:%s', $given::class)),
        retained: static fn(object $given): object => $given,
    );

    expect($answer)->toBe($value);
});

it('lets only a live reading confirm an action', function (): void {
    // N1-R24. A remembered "running", shown after a restart that failed, is
    // the application lying about the one moment somebody was watching.
    expect(Reading::live(readValue())->mayConfirmAnAction())->toBeTrue();
    expect(Reading::retained(readValue(), Instant::atEpochSeconds(1))->mayConfirmAnAction())->toBeFalse();
});
