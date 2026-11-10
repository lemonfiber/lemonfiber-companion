<?php

declare(strict_types=1);

namespace Modules\News\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\RequestId;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\NotAnItem;

it('names and orders each kind by what the stack vouches for', function (): void {
    $update = AnItem::anUpdate('0.17.0');
    $request = AnItem::aRequest(RequestId::named('42'));
    $problem = AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_000));

    expect([$update->kind(), $update->named(), $update->order()])->toBe([KindOfNews::Update, '0.17.0', 0])
        ->and([$request->kind(), $request->named(), $request->order()])->toBe([KindOfNews::Request, '42', 0])
        ->and([$problem->kind(), $problem->named(), $problem->order()])->toBe([KindOfNews::Problem, 'service.gluetun', 1_790_000_000]);
});

it('is the same item only where its kind, name and order all are', function (): void {
    $began = Instant::atEpochSeconds(1_790_000_000);

    expect(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.gluetun', $began)))->toBeTrue()
        ->and(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_001))))->toBeFalse()
        ->and(AnItem::aProblem('service.gluetun', $began)->is(AnItem::aProblem('service.sonarr', $began)))->toBeFalse()
        ->and(AnItem::anUpdate('1')->is(AnItem::aRequest(RequestId::named('1'))))->toBeFalse();
});

it('refuses an update or a problem named by nothing', function (): void {
    expect(static fn(): AnItem => AnItem::anUpdate('  '))->toThrow(NotAnItem::class, 'update')
        ->and(static fn(): AnItem => AnItem::aProblem(' ', Instant::atEpochSeconds(1)))->toThrow(NotAnItem::class, 'problem');
});

it('names a request by its reference as the stack wrote it', function (): void {
    expect(AnItem::aRequest(RequestId::named('music-3'))->named())->toBe('music-3')
        ->and(AnItem::aRequest(RequestId::named('music-3'))->is(AnItem::aRequest(RequestId::named('music-3'))))->toBeTrue()
        ->and(AnItem::aRequest(RequestId::named('music-3'))->is(AnItem::aRequest(RequestId::named('books-3'))))->toBeFalse();
});
