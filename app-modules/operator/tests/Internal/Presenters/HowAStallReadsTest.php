<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Operator\Internal\Presenters\HowAStallReads;

it('carries each limit the stack named, what and why, in its order', function (): void {
    $read = new HowAStallReads()->these(Stalled::of(
        HowMuchIsShown::AllOfIt,
        WhatIsUnsupported::these(
            Unsupported::of('The download client', 'It refused the credentials'),
            Unsupported::of('The indexer', 'It did not answer'),
        ),
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
    ));

    expect($read->unreached)->toHaveCount(2)
        ->and($read->unreached[0]->what)->toBe('The download client')
        ->and($read->unreached[0]->because)->toBe('It refused the credentials')
        ->and($read->unreached[1]->what)->toBe('The indexer')
        ->and($read->unreached[1]->because)->toBe('It did not answer')
        ->and($read->countSaid)->toBe(HowAStallReads::COUNTED_WHERE_IT_LOOKED)
        ->and($read->stalled)->toHaveCount(1);
});

it('counts plainly where the stack reached everything', function (): void {
    $read = new HowAStallReads()->these(Stalled::nothing());

    expect($read->unreached)->toBe([])
        ->and($read->countSaid)->toBe(HowAStallReads::COUNTED);
});

it('carries no limit and the plain count where nothing was read', function (): void {
    foreach ([new HowAStallReads()->signedOut(), new HowAStallReads()->met(Obstacle::StackDidNotAnswer)] as $read) {
        expect($read->unreached)->toBe([])
            ->and($read->countSaid)->toBe(HowAStallReads::COUNTED);
    }
});
