<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\ProblemSaysNothing;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;

/** A refusal with everything filled in, overridable a field at a time. */
function refusal(string $summary = 'The media drive is full', string $meaning = 'New downloads will fail until space is freed'): Problem
{
    return Problem::of(
        Code::of('STACK-7'),
        Severity::Error,
        Standing::Guided,
        $summary,
        $meaning,
        Remedies::of(Remedy::of('Free 20 GB on the media drive')),
    );
}

it('carries every field a screen reads', function (): void {
    $refusal = refusal();

    expect($refusal->code()->shown())->toBe('STACK-7')
        ->and($refusal->severity())->toBe(Severity::Error)
        ->and($refusal->standing())->toBe(Standing::Guided)
        ->and($refusal->summary())->toBe('The media drive is full')
        ->and($refusal->meaning())->toBe('New downloads will fail until space is freed')
        ->and($refusal->remedies()->count())->toBe(1);
});

it('trims the two sentences the operator reads', function (): void {
    $refusal = refusal("  The media drive is full \n", "\tNew downloads will fail  ");

    expect($refusal->summary())->toBe('The media drive is full')
        ->and($refusal->meaning())->toBe('New downloads will fail');
});

it('refuses a refusal with no summary', function (): void {
    expect(fn(): Problem => refusal(summary: ''))->toThrow(ProblemSaysNothing::class);
});

it('refuses a refusal with no meaning', function (): void {
    // Checked apart from the summary: one condition covering both would pass a
    // refusal that had only one of them, which is the screen with a heading and
    // nothing under it.
    expect(fn(): Problem => refusal(meaning: ''))->toThrow(ProblemSaysNothing::class);
});

it('refuses a summary that is only whitespace', function (): void {
    expect(fn(): Problem => refusal(summary: '   '))->toThrow(ProblemSaysNothing::class);
});

it('refuses a meaning that is only whitespace', function (): void {
    expect(fn(): Problem => refusal(meaning: "\n"))->toThrow(ProblemSaysNothing::class);
});

it('names the code when it refuses, because that is what gets searched for', function (): void {
    expect(fn(): Problem => refusal(summary: ''))
        ->toThrow(ProblemSaysNothing::class, 'STACK-7 arrived with no summary or no meaning');
});

it('carries no remedies where the server offered none', function (): void {
    $refusal = Problem::of(
        Code::of('STACK-9'),
        Severity::Critical,
        Standing::Unknown,
        'The stack cannot be reached',
        'Nothing can be read or changed until it answers',
        Remedies::none(),
    );

    expect($refusal->remedies()->count())->toBe(0);
});
