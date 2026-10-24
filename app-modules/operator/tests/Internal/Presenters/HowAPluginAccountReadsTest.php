<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Operator\Internal\Presenters\HowAPluginAccountReads;
use Modules\Operator\Internal\ViewModels\AValueCarriedAsShown;

use function sprintf;

use Tests\Support\APluginAsItArrives;
use Tests\Support\ARecipeRunAsItArrives;

it('says a pair asks for an approval exactly where the stack gave it one, apart from whether a switch is offered for it', function (): void {
    $shown = new HowAPluginAccountReads()->plugin(APluginAsItArrives::held(), HowItsSourceStands::notSaid(), [APluginAsItArrives::APPROVAL], TheAnswersOutOfContract::these());
    $pairs = [];

    foreach ($shown->recipes as $recipe) {
        $pairs = [...$pairs, ...$recipe->pairs];
    }

    expect(array_map(static fn(AValueCarriedAsShown $pair): string => sprintf('%s:%s:%s:%s', $pair->value, $pair->asksForApproval ? 'asks' : 'stays', $pair->approval ?? 'no switch', $pair->approved ? 'approved' : 'not approved'), $pairs))
        ->toBe(['api_key:stays:no switch:not approved', 'library:asks:0:approved']);
});

it('offers no install switch for a pair only a demand recipe carries, while saying it asks for an approval', function (): void {
    $shown = new HowAPluginAccountReads()->plugin(ARecipeRunAsItArrives::thePlugin(), HowItsSourceStands::notSaid(), [APluginAsItArrives::APPROVAL], TheAnswersOutOfContract::these());
    $pairs = [];

    foreach ($shown->recipes as $recipe) {
        $pairs = [...$pairs, ...$recipe->pairs];
    }

    expect(array_map(static fn(AValueCarriedAsShown $pair): string => sprintf('%s:%s:%s', $pair->value, $pair->asksForApproval ? 'asks' : 'stays', $pair->approval ?? 'no switch'), $pairs))
        ->toBe(['api_key:stays:no switch', 'library:asks:0', 'token:asks:no switch']);
});
