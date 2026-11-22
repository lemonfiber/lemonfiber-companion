<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAreaLeftAlone;
use Modules\Kernel\Api\AThingLeftAlone;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatAnAreaCovers;
use Modules\Kernel\Api\WhatIsLeftAlone;
use Modules\Kernel\Api\WhatIsLeftAloneMakesNoSense;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;

use function sprintf;

function anAreaCovering(AThingLeftAlone ...$things): AnAreaLeftAlone
{
    return AnAreaLeftAlone::declared('config/recyclarr', areaCut: false, why: 'my own profiles live in here', whyCut: true, covers: WhatAnAreaCovers::these(2, ...$things));
}

it('keeps an area exactly as the operator wrote it, cut or not, with everything it covers in order', function (): void {
    $service = AThingLeftAlone::of(WhatIsLeftAlone::Service, 'recyclarr');
    $path = AThingLeftAlone::of(WhatIsLeftAlone::Path, ' config/recyclarr/profiles ');
    $area = AnAreaLeftAlone::declared(' sonarr ', areaCut: true, why: ' I tune it ', whyCut: false, covers: WhatAnAreaCovers::these(3, $service, $path));

    expect([$area->area, $area->areaCut, $area->why, $area->whyCut, $area->covers->more(), count($area->covers)])
        ->toBe([' sonarr ', true, ' I tune it ', false, 3, 2])
        ->and(iterator_to_array($area->covers, preserve_keys: false))->toBe([$service, $path])
        ->and($path->name)->toBe(' config/recyclarr/profiles ');
});

it('keeps an area that covers nothing as covering nothing', function (): void {
    $area = anAreaCovering();

    expect([count($area->covers), $area->covers->more()])->toBe([0, 2]);
});

it('refuses a part that says nothing, naming it', function (callable $build, string $part): void {
    expect($build)->toThrow(WhatIsLeftAloneMakesNoSense::class, sprintf('what the stack leaves alone arrived with no %s', $part));
})->with([
    'an area' => [static fn(): object => AnAreaLeftAlone::declared(" \t", areaCut: false, why: 'why', whyCut: false, covers: WhatAnAreaCovers::these(0)), 'area'],
    'a reason' => [static fn(): object => AnAreaLeftAlone::declared('sonarr', areaCut: false, why: ' ', whyCut: false, covers: WhatAnAreaCovers::these(0)), 'reason'],
    'a covered name' => [static fn(): object => AThingLeftAlone::of(WhatIsLeftAlone::Setting, ''), 'name'],
]);

it('refuses a count below none, naming it, and takes none', function (): void {
    expect(static fn(): object => WhatAnAreaCovers::these(-1))->toThrow(WhatIsLeftAloneMakesNoSense::class, 'what the stack leaves alone arrived with covered things below none')
        ->and(static fn(): object => TheAreasLeftAlone::these(-1))->toThrow(WhatIsLeftAloneMakesNoSense::class, 'what the stack leaves alone arrived with declarations below none')
        ->and(TheAreasLeftAlone::these(0)->more())->toBe(0)
        ->and(WhatAnAreaCovers::these(0)->more())->toBe(0);
});

it('lists every area in the order declared, with how many more were left unlisted', function (): void {
    $first = anAreaCovering();
    $second = anAreaCovering(AThingLeftAlone::of(WhatIsLeftAlone::Setting, 'LEMONFIBER_FRONT_DOOR'));
    $areas = TheAreasLeftAlone::these(5, $first, $second);

    expect([count($areas), $areas->more()])->toBe([2, 5])
        ->and(iterator_to_array($areas, preserve_keys: false))->toBe([$first, $second]);
});

it('says each kind on the screen by its own key', function (WhatIsLeftAlone $kind): void {
    expect($kind->saidOnTheScreen())->toBe(sprintf('stacks.left_alone.kind.%s', $kind->value));
})->with(WhatIsLeftAlone::cases());

it('folds each answer into its own arm', function (): void {
    $areas = TheAreasLeftAlone::these(0);
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $listed = static fn(TheAreasLeftAlone $got): ArrayObject => new ArrayObject(['listed', $got]);
    $notCarried = static fn(): ArrayObject => new ArrayObject(['not carried']);
    $met = static fn(Obstacle $got): ArrayObject => new ArrayObject(['met', $got]);

    expect(WhatWasFoundOfTheAreasLeftAlone::listed($areas)->either($listed, $notCarried, $met)->getArrayCopy())->toBe(['listed', $areas])
        ->and(WhatWasFoundOfTheAreasLeftAlone::notCarried()->either($listed, $notCarried, $met)->getArrayCopy())->toBe(['not carried'])
        ->and(WhatWasFoundOfTheAreasLeftAlone::met($why)->either($listed, $notCarried, $met)->getArrayCopy())->toBe(['met', $why]);
});
