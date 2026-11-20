<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheDoorSaysNothing;

it('holds the code, the page to approve it at, when it lapses and its handle', function (): void {
    $code = ACodeToApprove::issued('ABCD', 'https://approve.example/link', Instant::atEpochSeconds(90), AWaitingCode::answered('a-handle'));

    expect([$code->code, $code->approveAt->url(), $code->lapses->epochSeconds(), $code->waiting->forTheExchange()])
        ->toBe(['ABCD', 'https://approve.example/link', 90, 'a-handle']);
});

it('refuses a code that says nothing, or nowhere to approve it', function (string $code, string $at): void {
    expect(static fn(): ACodeToApprove => ACodeToApprove::issued($code, $at, Instant::atEpochSeconds(90), AWaitingCode::answered('a-handle')))
        ->toThrow(TheDoorSaysNothing::class);
})->with([
    'no code' => [' ', 'https://approve.example/link'],
    'nowhere to approve it' => ['ABCD', ' '],
]);
