<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function mb_strlen;

use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\PairingIsNotReadable;

use function print_r;
use function serialize;
use function sprintf;
use function str_repeat;
use function unserialize;

/** What a stack says replacing its certificate would cost. */
const A_CERTIFICATE_REPLACED_COSTS = 'Every phone paired with this machine refuses it from then on, until it is paired again with new material.';

/** A code as the stack makes one, with one word changed where a case says. */
function aPairingCode(
    string $compare = '22VK-KPHH-NKH9-TUWA',
    string $address = 'https://den.local:8443',
    string $replacing = A_CERTIFICATE_REPLACED_COSTS,
    string $fingerprint = '0',
): APairingCode {
    return APairingCode::made(
        APairingLine::asWritten('{"address":"https://den.local:8443"}'),
        Fingerprint::of(str_repeat($fingerprint, Fingerprint::CHARACTERS)),
        $compare,
        Instant::atEpochSeconds(1_790_813_400),
        $address,
        '',
        $replacing,
    );
}

it('holds every word the stack made it with', function (): void {
    $code = APairingCode::made(
        APairingLine::asWritten('the line'),
        Fingerprint::of(str_repeat('0', Fingerprint::CHARACTERS)),
        'ABCD-EFGH',
        Instant::atEpochSeconds(1_790_813_400),
        'https://den.local:8443',
        'That address is a number.',
        A_CERTIFICATE_REPLACED_COSTS,
    );

    expect($code->line()->carried())->toBe('the line')
        ->and($code->compare())->toBe('ABCD-EFGH')
        ->and($code->expiresAt()->epochSeconds())->toBe(1_790_813_400)
        ->and($code->address())->toBe('https://den.local:8443')
        ->and($code->caution())->toBe('That address is a number.')
        ->and($code->replacing())->toBe(A_CERTIFICATE_REPLACED_COSTS);
});

it('agrees on the check code where the stack folded its fingerprint as this phone does', function (): void {
    expect(aPairingCode()->agreesOnTheCheckCode())->toBeTrue()
        ->and(aPairingCode(compare: 'Z9JL-Q3PK-BZ6M-HRQZ', fingerprint: 'f')->agreesOnTheCheckCode())->toBeTrue();
});

it('does not agree on the check code where the stack folded its fingerprint differently', function (): void {
    expect(aPairingCode(compare: 'Z9JL-Q3PK-BZ6M-HRQZ')->agreesOnTheCheckCode())->toBeFalse()
        ->and(aPairingCode(compare: '22vk-kphh-nkh9-tuwa')->agreesOnTheCheckCode())->toBeFalse()
        ->and(aPairingCode(fingerprint: 'f')->agreesOnTheCheckCode())->toBeFalse();
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
    'replacing' => ['replacing', static fn(): APairingCode => aPairingCode(replacing: '')],
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
