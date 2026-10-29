<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginActWasNotRehearsed;
use Modules\Kernel\Api\ARecipeRunAgreed;
use Modules\Kernel\Api\PluginLines;
use Tests\Support\ARecipeRunAsItArrives;

it('refuses a yes to running a recipe against a reading that names no agreement to quote', function (): void {
    expect(static fn(): ARecipeRunAgreed => ARecipeRunAgreed::after(ARecipeRunAsItArrives::theAnswer(ran: false, agreement: ''), ARecipeRunAsItArrives::thePlugin(), ARecipeRunAsItArrives::theRecipe(), PluginLines::none()))
        ->toThrow(APluginActWasNotRehearsed::class);
});
