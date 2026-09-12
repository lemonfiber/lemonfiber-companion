<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Outcome;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

use function sprintf;

/** What a command hands back when it worked, as a named type rather than a stub. */
function repaired(): Code
{
    return Code::of('STACK-7');
}

function refused(): Problem
{
    return Problem::of(
        Code::of('STACK-9'),
        Severity::Error,
        Standing::Guided,
        'The media drive is full',
        'New downloads will fail until space is freed',
        Remedies::none(),
    );
}

/**
 * Both branches, each naming itself and what it was handed.
 *
 * Written once rather than per test so that every case reads the same fold,
 * and so that neither branch can quietly stop using what it was given — an arm
 * that ignores its argument would still pass a test that only asked which side
 * ran.
 *
 * Both arms answer with a `Code` because `either` answers with an object, and
 * that is the signature rather than an accident of this test: two arms free to
 * return anything are two arms that can return different things, and the
 * caller is back to branching a second time.
 */
function fold(Outcome $outcome): Code
{
    return $outcome->either(
        done: static fn(object $result): Code => Code::of(sprintf('done:%s', $result::class)),
        refused: static fn(Problem $refusal): Code => Code::of(sprintf('refused:%s', $refusal->code()->shown())),
    );
}

it('takes the done branch when it happened', function (): void {
    expect(fold(Outcome::done(repaired()))->shown())->toBe(sprintf('done:%s', Code::class));
});

it('takes the refused branch when it did not', function (): void {
    expect(fold(Outcome::refused(refused()))->shown())->toBe('refused:STACK-9');
});

it('hands the result itself to the done branch', function (): void {
    $result = repaired();

    $answer = Outcome::done($result)->either(
        done: static fn(object $given): object => $given,
        refused: static fn(Problem $refusal): object => $refusal,
    );

    expect($answer)->toBe($result);
});

it('hands the refusal itself to the refused branch', function (): void {
    $refusal = refused();

    $answer = Outcome::refused($refusal)->either(
        done: static fn(object $given): object => $given,
        refused: static fn(Problem $given): object => $given,
    );

    expect($answer)->toBe($refusal);
});

it('answers with whatever the branch that ran returned', function (): void {
    // The fold is the point: a caller gets one value back regardless of which
    // way it went, which is what lets a presenter build one view model without
    // branching a second time.
    $answer = Outcome::refused(refused())->either(
        done: static fn(object $given): Code => Code::of($given::class),
        refused: static fn(Problem $given): Code => $given->code(),
    );

    expect($answer->shown())->toBe('STACK-9');
});
