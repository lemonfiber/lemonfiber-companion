<?php

declare(strict_types=1);

use Modules\Kernel\Api\Stream;

it('N2-R10 — says which mouth a line came out of, as a key', function (): void {
    expect(Stream::Stdout->saidOnTheScreen())->toBe('health.stream.stdout')
        ->and(Stream::Stderr->saidOnTheScreen())->toBe('health.stream.stderr');
});

it('one of the two is worth letting stand out', function (): void {
    expect(Stream::Stderr->worthNoticing())->toBeTrue()
        ->and(Stream::Stdout->worthNoticing())->toBeFalse();
});

it('worth noticing is deliberately weaker than wrong', function (): void {
    // Plenty of well-behaved services write ordinary progress to `stderr`, so a
    // screen treating this as *error* would put a red mark against a service
    // that is working. That judgement belongs to `Severity`, which comes from a
    // check that decided something.
    expect(Stream::cases())->toHaveCount(2);
});
