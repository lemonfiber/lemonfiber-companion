<?php

declare(strict_types=1);

use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;

/** What a stack names when it refuses a bundle holding a credential. */
const WHERE_A_CREDENTIAL_SITS = 'sonarr/config.xml line 12 — nothing was written';

it('hands the operator what the stack named, trimmed', function (): void {
    expect(WhatTheRefusalNamed::as(sprintf('  %s ', WHERE_A_CREDENTIAL_SITS))->forTheOperator())->toBe(WHERE_A_CREDENTIAL_SITS);
});

it('reads a refusal that named nothing, or only spaces, as naming nothing', function (string $said): void {
    expect(WhatTheRefusalNamed::as($said)->forTheOperator())->toBe('')
        ->and(WhatTheRefusalNamed::nothing()->forTheOperator())->toBe('');
})->with([
    'blank' => [''],
    'only spaces' => ['   '],
]);

it('does not print itself, and says something is there in its place', function (): void {
    expect(WhatTheRefusalNamed::as(WHERE_A_CREDENTIAL_SITS)->__debugInfo())->toBe(['said' => '(what a refusal named, hidden)'])
        ->and(json_encode(['named' => WhatTheRefusalNamed::as(WHERE_A_CREDENTIAL_SITS)]))->toBe('{"named":"(what a refusal named, hidden)"}')
        ->and(print_r(WhatTheRefusalNamed::as(WHERE_A_CREDENTIAL_SITS), return: true))->not->toContain('sonarr');
});

it('is written into a log line as hidden, however it reaches the context', function (): void {
    $line = new JsonFormatter()->format(new LogRecord(
        new DateTimeImmutable('2026-09-27T21:00:00Z'),
        'app',
        Level::Warning,
        'A bundle was refused',
        ['named' => WhatTheRefusalNamed::as(WHERE_A_CREDENTIAL_SITS)],
    ));

    expect($line)->not->toContain('sonarr')
        ->and($line)->toContain('(what a refusal named, hidden)');
});

it('does not leave the process in a serialised payload', function (): void {
    expect(fn(): string => serialize(WhatTheRefusalNamed::as(WHERE_A_CREDENTIAL_SITS)))
        ->toThrow(MustNotLeaveThisProcess::class, 'What a refusal named may not be serialised');
});

it('does not come back from a serialised payload either', function (): void {
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(WhatTheRefusalNamed::class), WhatTheRefusalNamed::class);

    expect(fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class, 'What a refusal named may not be serialised');
});
