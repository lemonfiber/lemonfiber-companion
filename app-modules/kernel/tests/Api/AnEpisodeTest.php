<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\WhereItPlays;

it('holds an episode as the core answered it, by the id the core lists it under', function (): void {
    $episode = AnEpisode::of(HoldingId::called('e1'), 'One', NumberedAs::none(), HowLongItRuns::minutes(49), 'It begins.', WhereItPlays::doesNotStream());

    expect($episode->id()->named())->toBe('e1')
        ->and($episode->titled())->toBe('One')
        ->and($episode->about())->toBe('It begins.');
});
