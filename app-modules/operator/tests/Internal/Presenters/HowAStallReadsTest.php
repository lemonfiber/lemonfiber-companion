<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Operator\Internal\Presenters\HowAStallReads;

it('carries each stalled item in the stack\'s order, with how much of the listing this is', function (): void {
    $read = new HowAStallReads()->these(Stalled::of(
        HowMuchIsShown::SomeOfIt,
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
        Stuck::at('A series somebody has', 'sonarr', Stage::Downloaded),
    ));

    expect($read->went->cameBack())->toBeTrue()
        ->and($read->stalled)->toHaveCount(2)
        ->and($read->stalled[0]->title)->toBe('A film nobody has seen')
        ->and($read->stalled[1]->title)->toBe('A series somebody has')
        ->and($read->shownSaid)->toBe(HowMuchIsShown::SomeOfIt->saidOnTheScreen());
});

it('carries no item and no listing where nothing was read', function (): void {
    foreach ([new HowAStallReads()->signedOut(), new HowAStallReads()->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))] as $read) {
        expect($read->went->cameBack())->toBeFalse()
            ->and($read->stalled)->toBe([])
            ->and($read->shownSaid)->toBe('');
    }
});
