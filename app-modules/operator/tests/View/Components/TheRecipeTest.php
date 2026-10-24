<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Operator\Internal\Presenters\HowAPluginAccountReads;
use Tests\Support\APluginAsItArrives;
use Tests\Support\ARecipeRunAsItArrives;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

uses(TestCase::class);

it('says a demand recipe\'s pair beneath an install is approved each time it runs, never that it stays on this machine', function (): void {
    $shown = new HowAPluginAccountReads()->plugin(ARecipeRunAsItArrives::thePlugin(), HowItsSourceStands::notSaid(), [APluginAsItArrives::APPROVAL], TheAnswersOutOfContract::these());
    $drawn = '';

    foreach ($shown->recipes as $recipe) {
        $drawn .= WhatMarkupDraws::outline('<x-operator::the-recipe :recipe="$recipe" :agreeable="true" />', ['recipe' => $recipe]);
    }

    expect(substr_count($drawn, WhatMarkupDraws::words('plugins.asked_each_run')))->toBe(1)
        ->and(substr_count($drawn, WhatMarkupDraws::words('plugins.stays_here')))->toBe(1)
        ->and($drawn)->not->toContain(WhatMarkupDraws::words('plugins.not_asked_here'));
});
