<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\RoomSaysNothing;

/** One line carried out of an arm. */
final readonly class WhichArmTheRatioTook
{
    public function __construct(public string $said) {}
}

/** Which arm a ratio takes, and what it carried there. */
function howTheRatioReads(ARatio $ratio): string
{
    return $ratio->either(
        read: static fn(string $read): WhichArmTheRatioTook => new WhichArmTheRatioTook($read),
        none: static fn(): WhichArmTheRatioTook => new WhichArmTheRatioTook('none'),
    )->said;
}

it('N12-R2 — reads hundredths as a person does', function (int $hundredths, string $reads): void {
    expect(howTheRatioReads(ARatio::inHundredths($hundredths)))->toBe($reads);
})->with([[0, '0.00'], [5, '0.05'], [99, '0.99'], [100, '1.00'], [125, '1.25'], [1_010, '10.10']]);

it('N12-R2 — no ratio is its own answer, never a figure', function (): void {
    expect(howTheRatioReads(ARatio::none()))->toBe('none');
});

it('refuses a ratio below nothing', function (): void {
    expect(fn(): ARatio => ARatio::inHundredths(-1))->toThrow(RoomSaysNothing::class, '`ratio`');
});
