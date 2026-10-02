<?php

declare(strict_types=1);

namespace Modules\News\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\NotAnItem;

it('names and orders each kind by what the stack vouches for', function (): void {
    $update = AnItem::anUpdate('0.17.0');
    $request = AnItem::aRequest(42);
    $problem = AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_000));

    expect([$update->kind(), $update->named(), $update->order()])->toBe([KindOfNews::Update, '0.17.0', 0])
        ->and([$request->kind(), $request->named(), $request->order()])->toBe([KindOfNews::Request, '42', 42])
        ->and([$problem->kind(), $problem->named(), $problem->order()])->toBe([KindOfNews::Problem, 'service.gluetun', 1_790_000_000]);
});

it('is the same item only where its kind, name and order all are', function (): void {
    $began = Instant::atEpochSeconds(1_790_000_000);

    expect(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.gluetun', $began)))->toBeTrue()
        ->and(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_001))))->toBeFalse()
        ->and(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.sonarr', $began)))->toBeFalse()
        ->and(AnItem::anUpdate('1')->is(AnItem::aRequest(1)))->toBeFalse();
});

it('refuses an item named by nothing, and a request numbered below one', function (): void {
    expect(static fn(): AnItem => AnItem::anUpdate('  '))->toThrow(NotAnItem::class, 'update')
        ->and(static fn(): AnItem => AnItem::aProblem('', Instant::atEpochSeconds(1)))->toThrow(NotAnItem::class, 'problem')
        ->and(static fn(): AnItem => AnItem::aRequest(0))->toThrow(NotAnItem::class, 'numbered 0');
});

it('takes a request numbered one', function (): void {
    expect(AnItem::aRequest(1)->order())->toBe(1);
});
