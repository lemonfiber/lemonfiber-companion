<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\TheDoorSaysNothing;

use function print_r;
use function serialize;
use function unserialize;
use function var_export;

it('hands the handle to the request that finishes the sign-in', function (): void {
    expect(AWaitingCode::answered('a-handle')->forTheExchange())->toBe('a-handle');
});

it('refuses a handle that says nothing', function (): void {
    expect(static fn(): AWaitingCode => AWaitingCode::answered('  '))->toThrow(TheDoorSaysNothing::class);
});

it('keeps the handle out of every dump and export, and refuses to be serialised either way', function (): void {
    $waiting = AWaitingCode::answered('a-handle-nobody-sees');

    expect(print_r($waiting, return: true))->not->toContain('a-handle-nobody-sees')
        ->and(var_export($waiting, return: true))->not->toContain('a-handle-nobody-sees')
        ->and($waiting->__debugInfo())->toBe(['handle' => '(a waiting code, hidden)'])
        ->and(static fn(): string => serialize($waiting))->toThrow(MustNotLeaveThisProcess::class)
        ->and(static fn(): mixed => unserialize('O:31:"Modules\Kernel\Api\AWaitingCode":0:{}'))->toThrow(MustNotLeaveThisProcess::class);
});
