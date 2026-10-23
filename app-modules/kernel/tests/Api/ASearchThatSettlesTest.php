<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASearchThatSettles;
use Modules\Kernel\Api\TheTraceSaysNothing;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhetherASearchSettles;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

it('carries what a search spends and covers, trimmed, and refuses a blank one, naming it', function (): void {
    $search = ASearchThatSettles::spending(' One search of each indexer ', ' Everything the curator wants ');

    expect([$search->spends(), $search->covers()])->toBe(['One search of each indexer', 'Everything the curator wants'])
        ->and(static fn(): ASearchThatSettles => ASearchThatSettles::spending(' ', 'Everything the curator wants'))->toThrow(TheTraceSaysNothing::class, '`spends`')
        ->and(static fn(): ASearchThatSettles => ASearchThatSettles::spending('One search', ''))->toThrow(TheTraceSaysNothing::class, '`covers`');
});

it('takes the arm it was made on', function (): void {
    $arm = static fn(WhetherASearchSettles $whether): string => $whether->either(
        so: static fn(ASearchThatSettles $search): TheWordCarriedOut => new TheWordCarriedOut($search->spends()),
        not: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not'),
    )->said;

    expect($arm(WhetherASearchSettles::so(ASearchThatSettles::spending('One search', 'Everything'))))->toBe('One search')
        ->and($arm(WhetherASearchSettles::not()))->toBe('not');
});

it('follows one season of an item, or every season, and refuses a season below none or a blank term', function (): void {
    $said = static fn(WhatToFollow $following): string => $following->either(
        narrowed: static fn(int $season): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s/%d', $following->term(), $season)),
        whole: static fn(): TheWordCarriedOut => new TheWordCarriedOut($following->term()),
    )->said;

    expect($said(WhatToFollow::calledInSeason(' Severance ', 2)))->toBe('Severance/2')
        ->and($said(WhatToFollow::calledInSeason('Severance', 0)))->toBe('Severance/0')
        ->and($said(WhatToFollow::called('Severance')))->toBe('Severance')
        ->and(static fn(): WhatToFollow => WhatToFollow::calledInSeason('Severance', -1))->toThrow(TheTraceSaysNothing::class, '`season`')
        ->and(static fn(): WhatToFollow => WhatToFollow::calledInSeason(' ', 1))->toThrow(TheTraceSaysNothing::class, '`term`');
});
