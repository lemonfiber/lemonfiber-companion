<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function mb_strlen;

use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\PairingIsNotReadable;

use function print_r;
use function serialize;
use function sprintf;
use function unserialize;

const A_LINE = '{"address":"https://den.local:8443","fingerprint":"ab","expires":1790813400,"stack":"00"}';

it('carries the line exactly as the stack wrote it', function (): void {
    expect(APairingLine::asWritten(A_LINE)->carried())->toBe(A_LINE)
        ->and(APairingLine::asWritten(' x ')->carried())->toBe(' x ');
});

it('refuses a blank line, since a code of nothing pairs nothing', function (): void {
    expect(static fn(): APairingLine => APairingLine::asWritten("  \n"))
        ->toThrow(PairingIsNotReadable::class, 'The stack answered with a blank line to pair with, and a code of nothing pairs nothing.');
});

it('says what it is in a dump and not what it says', function (): void {
    $dumped = print_r(APairingLine::asWritten(A_LINE), return: true);

    expect($dumped)->toContain('(a pairing line, hidden)')
        ->and($dumped)->not->toContain('den.local');
});

it('refuses to leave the process', function (): void {
    expect(static fn(): string => serialize(APairingLine::asWritten(A_LINE)))
        ->toThrow(MustNotLeaveThisProcess::class, 'A pairing line may not be serialised.');
});

it('refuses to be read back into the process', function (): void {
    $crafted = sprintf('O:%d:"%s":0:{}', mb_strlen(APairingLine::class), APairingLine::class);

    expect(static fn(): mixed => unserialize($crafted))
        ->toThrow(MustNotLeaveThisProcess::class, 'A pairing line may not be serialised.');
});
