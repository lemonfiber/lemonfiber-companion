<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function __;
use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;
use Modules\Household\Internal\ViewModels\WhatStoppedAMember;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

use function uses;

uses(TestCase::class);

/** What the component draws for what came of pressing a channel. */
function besideIt(?WhatAChannelCameTo $came): string
{
    return WhatMarkupDraws::outline('<native:column><x-household::beside-a-channel :came="$came" /></native:column>', ['came' => $came]);
}

it('says nothing where nothing came of it', function (): void {
    expect(besideIt(null))->toEndWith('[]');
});

it('says the core\'s own words beside the channel as they were written', function (): void {
    expect(besideIt(WhatAChannelCameTo::said('NPO 1', 'Live TV plays only at home.')))->toContain('NPO 1: Live TV plays only at home.');
});

it('says one of this app\'s lines beside the channel through the catalogue', function (): void {
    $outline = besideIt(WhatAChannelCameTo::inOurWords('NPO 1', 'household.signed_out'));

    expect($outline)->toContain('NPO 1: ')
        ->and($outline)->toContain(__('household.signed_out'))
        ->and($outline)->not->toContain('household.signed_out');
});

it('says what stood in the way under the channel\'s name', function (): void {
    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $outline = besideIt(WhatAChannelCameTo::stopped('NPO 1', WhatStoppedAMember::by($why)));

    expect($outline)->toContain('NPO 1')
        ->and($outline)->toContain(__($why->saidToTheHousehold()));
});
