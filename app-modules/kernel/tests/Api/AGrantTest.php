<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;
use function mb_strlen;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\GrantIsUnfit;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\MustNotLeaveThisProcess;

use function print_r;
use function serialize;
use function sprintf;
use function str_repeat;
use function unserialize;

/** A grant as the core answers one. Named for this file (G10). */
function aGrantTheCoreAnswered(): AGrant
{
    return AGrant::of('0123456789abcdef0123456789abcdef', Instant::atEpochSeconds(1_000));
}

it('carries the grant exactly as the core answered it, and when it lapses', function (): void {
    expect(aGrantTheCoreAnswered()->forTheDoor())->toBe('0123456789abcdef0123456789abcdef')
        ->and(aGrantTheCoreAnswered()->lapsesAt()->epochSeconds())->toBe(1_000);
});

it('refuses a grant the door would not accept', function (string $token): void {
    expect(static fn(): AGrant => AGrant::of($token, Instant::atEpochSeconds(1)))->toThrow(GrantIsUnfit::class);
})->with([
    'upper case' => [str_repeat('A', 32)],
    'one short' => [str_repeat('a', 31)],
    'one long' => [str_repeat('a', 33)],
    'a letter past f' => [sprintf('%sg', str_repeat('a', 31))],
    'quoted' => [sprintf('"%s"', str_repeat('a', 30))],
    'a line break after it' => [sprintf("%s\n", str_repeat('a', 32))],
]);

it('stands until the moment it lapses, and not from then', function (): void {
    expect(aGrantTheCoreAnswered()->hasLapsedBy(Instant::atEpochSeconds(999)))->toBeFalse()
        ->and(aGrantTheCoreAnswered()->hasLapsedBy(Instant::atEpochSeconds(1_000)))->toBeTrue()
        ->and(aGrantTheCoreAnswered()->hasLapsedBy(Instant::atEpochSeconds(1_001)))->toBeTrue();
});

it('says nothing of itself where it is printed or encoded', function (): void {
    expect(print_r(aGrantTheCoreAnswered(), return: true))->not->toContain('0123456789abcdef')
        ->and((string) json_encode(aGrantTheCoreAnswered()))->not->toContain('0123456789abcdef')
        ->and(aGrantTheCoreAnswered()->__debugInfo())->toBe(['token' => '(a grant, hidden)']);
});

it('refuses to be written down or read back', function (): void {
    expect(static fn(): string => serialize(aGrantTheCoreAnswered()))->toThrow(MustNotLeaveThisProcess::class)
        ->and(static fn(): mixed => unserialize(sprintf('O:%d:"%s":0:{}', mb_strlen(AGrant::class), AGrant::class)))
        ->toThrow(MustNotLeaveThisProcess::class);
});
