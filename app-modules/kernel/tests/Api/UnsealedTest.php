<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;
use function mb_strlen;

use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\Unsealed;

use function print_r;
use function serialize;
use function sprintf;
use function str_contains;
use function unserialize;

/** What a stack might say about a household, which is why it is hidden. */
const WHAT_A_STACK_SAID = '{"member":"somebody in the house"}';

it('carries what it was given exactly, an empty value included', function (): void {
    expect(Unsealed::of(WHAT_A_STACK_SAID)->inTheClear())->toBe(WHAT_A_STACK_SAID)
        ->and(Unsealed::of(' padded ')->inTheClear())->toBe(' padded ')
        ->and(Unsealed::of('')->inTheClear())->toBe('');
});

it('does not print itself into a debugger or a payload', function (): void {
    $value = Unsealed::of(WHAT_A_STACK_SAID);

    expect($value->__debugInfo())->toBe(['value' => '(unsealed, hidden)'])
        ->and(json_encode(['value' => $value]))->toBe('{"value":"(unsealed, hidden)"}')
        ->and(str_contains(print_r($value, return: true), 'somebody in the house'))->toBeFalse();
});

it('does not leave the process in a serialised payload', function (): void {
    expect(fn(): string => serialize(Unsealed::of(WHAT_A_STACK_SAID)))
        ->toThrow(MustNotLeaveThisProcess::class, 'An unsealed value may not be serialised.');
});

it('does not come back from a serialised payload either', function (): void {
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(Unsealed::class), Unsealed::class);

    expect(fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class, 'An unsealed value may not be serialised.');
});
