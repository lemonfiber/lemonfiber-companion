<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use DateTimeImmutable;

use function expect;
use function it;
use function json_encode;
use function mb_strlen;

use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;

use function print_r;
use function serialize;
use function sprintf;
use function unserialize;

/** A bundle's file, holding a line that must never reach anything but the sheet. */
function aBundleFileHolding(string $bytes): ABundleFile
{
    return ABundleFile::fetched(AWrittenBundle::at('/home/op/bundles/lemonfiber-support.tar.gz'), $bytes);
}

it('is named for the written bundle, and holds the bytes as they arrived', function (): void {
    $file = aBundleFileHolding("\x1F\x8B\x08\x00SONARR_URL=http://sonarr:8989");

    expect($file->named())->toBe('lemonfiber-support.tar.gz')
        ->and($file->bytes())->toBe("\x1F\x8B\x08\x00SONARR_URL=http://sonarr:8989");
});

it('shows its name and its size, and nothing of what it holds', function (): void {
    $file = aBundleFileHolding('SONARR_URL=http://sonarr:8989');

    expect($file->__debugInfo())->toBe(['named' => 'lemonfiber-support.tar.gz', 'bytes' => '(29 bytes, hidden)'])
        ->and(json_encode(['bundle' => $file]))->toBe('{"bundle":"lemonfiber-support.tar.gz (29 bytes, hidden)"}')
        ->and(print_r($file, return: true))->not->toContain('sonarr');
});

it('is written into a log line as hidden, however it reaches the context', function (): void {
    $line = new JsonFormatter()->format(new LogRecord(
        new DateTimeImmutable('2026-09-27T21:00:00Z'),
        'app',
        Level::Warning,
        'A bundle was handed over',
        ['bundle' => aBundleFileHolding('SONARR_URL=http://sonarr:8989')],
    ));

    expect($line)->not->toContain('sonarr')
        ->and($line)->toContain('(29 bytes, hidden)');
});

it('does not leave the process in a serialised payload', function (): void {
    expect(static fn(): string => serialize(aBundleFileHolding('a')))
        ->toThrow(MustNotLeaveThisProcess::class, 'A support bundle may not be serialised');
});

it('does not come back from a serialised payload either', function (): void {
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(ABundleFile::class), ABundleFile::class);

    expect(static fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class, 'A support bundle may not be serialised');
});
