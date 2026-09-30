<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function mb_strlen;

use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\PairingIsNotReadable;

use function print_r;
use function serialize;
use function sprintf;
use function unserialize;

/** A code as the stack makes one, with one word changed where a case says. */
function aPairingCode(string $compare = '22VK-KPHH-NKH9-TUWA', string $address = 'https://den.local:8443'): APairingCode
{
    return APairingCode::made(APairingLine::asWritten('{"address":"https://den.local:8443"}'), $compare, Instant::atEpochSeconds(1_790_813_400), $address, '');
}

it('holds every word the stack made it with', function (): void {
    $code = APairingCode::made(APairingLine::asWritten('the line'), 'ABCD-EFGH', Instant::atEpochSeconds(1_790_813_400), 'https://den.local:8443', 'That address is a number.');

    expect($code->line()->carried())->toBe('the line')
        ->and($code->compare())->toBe('ABCD-EFGH')
        ->and($code->expiresAt()->epochSeconds())->toBe(1_790_813_400)
        ->and($code->address())->toBe('https://den.local:8443')
        ->and($code->caution())->toBe('That address is a number.');
});

it('stops being good at the moment it names, and not before', function (): void {
    expect(aPairingCode()->hasExpiredBy(Instant::atEpochSeconds(1_790_813_399)))->toBeFalse()
        ->and(aPairingCode()->hasExpiredBy(Instant::atEpochSeconds(1_790_813_400)))->toBeTrue()
        ->and(aPairingCode()->hasExpiredBy(Instant::atEpochSeconds(1_790_813_401)))->toBeTrue();
});

it('refuses a blank word it owes, naming it', function (string $field, callable $made): void {
    expect($made)->toThrow(PairingIsNotReadable::class, sprintf('pairing code whose `%s` is blank', $field));
})->with([
    'compare' => ['compare', static fn(): APairingCode => aPairingCode(compare: ' ')],
    'address' => ['address', static fn(): APairingCode => aPairingCode(address: "\n")],
]);

it('says what it is in a dump and not what it says', function (): void {
    $dumped = print_r(aPairingCode(), return: true);

    expect($dumped)->toContain('(a pairing code, hidden)')
        ->and($dumped)->not->toContain('den.local');
});

it('refuses to leave the process, or to be read back into it', function (): void {
    $crafted = sprintf('O:%d:"%s":0:{}', mb_strlen(APairingCode::class), APairingCode::class);

    expect(static fn(): string => serialize(aPairingCode()))->toThrow(MustNotLeaveThisProcess::class)
        ->and(static fn(): mixed => unserialize($crafted))->toThrow(MustNotLeaveThisProcess::class);
});
