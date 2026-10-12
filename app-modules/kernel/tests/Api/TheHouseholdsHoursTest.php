<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\TheHouseholdsHours;

it('keeps the hours as the stack wrote them, less the space around them, and a zone it did not give as blank', function (): void {
    $hours = TheHouseholdsHours::from(' 07:00 ', ' 23:00 ', '');

    expect([$hours->starts(), $hours->ends(), $hours->zone()])->toBe(['07:00', '23:00', ''])
        ->and(TheHouseholdsHours::from('07:00', '01:00', ' Europe/Amsterdam ')->zone())->toBe('Europe/Amsterdam');
});

it('refuses hours with either end blank, naming it', function (string $from, string $to, string $named): void {
    expect(static fn(): TheHouseholdsHours => TheHouseholdsHours::from($from, $to, ''))
        ->toThrow(LineSaysNothing::class, $named);
})->with([
    'the start' => [' ', '23:00', '`rhythm.from`'],
    'the end' => ['07:00', '', '`rhythm.to`'],
]);
