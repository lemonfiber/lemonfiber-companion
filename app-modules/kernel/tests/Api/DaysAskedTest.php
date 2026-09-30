<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\DaysAsked;
use Modules\Kernel\Api\HowLongReadingsAreKept;

/** What came of asking, as one word: the count allowed, or refused. */
function whatCameOfAsking(DaysAsked $asked): string
{
    return $asked->either(
        allowed: static fn(HowLongReadingsAreKept $kept): Code => $kept->either(
            days: static fn(int $days): Code => Code::of((string) $days),
            untilRemoved: static fn(): Code => Code::of('until removed'),
        ),
        refused: static fn(): Code => Code::of('refused'),
    )->shown();
}

it('allows a whole count of days from one to a year', function (int $days): void {
    expect(whatCameOfAsking(DaysAsked::counted($days)))->toBe((string) $days);
})->with([1, 45, 365]);

it('refuses a count below one or past a year', function (int $days): void {
    expect(whatCameOfAsking(DaysAsked::counted($days)))->toBe('refused');
})->with([0, 366, -7]);

it('reads a typed count, around any spaces', function (): void {
    expect(whatCameOfAsking(DaysAsked::typed(' 45 ')))->toBe('45');
});

it('refuses typing that is not a whole count', function (string $typed): void {
    expect(whatCameOfAsking(DaysAsked::typed($typed)))->toBe('refused');
})->with(['nothing' => [''], 'a word' => ['a week'], 'a fraction' => ['4.5'], 'a sign' => ['-3'], 'too many' => ['99999999999999999999999']]);
