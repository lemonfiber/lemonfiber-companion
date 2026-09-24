<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\ARequestOfTheirs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OurRequests;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheirRequests;
use Modules\Kernel\Api\WhatLeavesThisMachine;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhatWasFoundLeaving;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;

use function sprintf;

/** Fetching images, one of lemonfiber's own requests, as a stack would describe it. */
function aRequestOfOursAboutImages(): ARequestOfOurs
{
    return ARequestOfOurs::described(
        WhatLemonfiberAsksFor::Registry,
        WhereItGoes::to('ghcr.io'),
        'To fetch the images the stack runs',
        'The name and version of each image',
        WhetherItIsAllowed::Allowed,
        'registry.pull',
        'No service can be installed or updated',
    );
}

/** One line carried out of an arm. */
final readonly class WhichArmTheLeavingTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function whatWasFoundLeaving(WhatWasFoundLeaving $answer): string
{
    return $answer->either(
        leaving: static fn(WhatLeavesThisMachine $leaving): WhichArmTheLeavingTook => new WhichArmTheLeavingTook(sprintf('leaving:%d/%d', $leaving->ours()->count(), $leaving->theirs()->count())),
        met: static fn(Obstacle $why): WhichArmTheLeavingTook => new WhichArmTheLeavingTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('N10-R1 — keeps lemonfiber\'s requests and its services\' apart', function (): void {
    $leaving = WhatLeavesThisMachine::of(
        OurRequests::of(aRequestOfOursAboutImages()),
        TheirRequests::of(ARequestOfTheirs::unrecorded(ServiceId::called('my-fork')), ARequestOfTheirs::recorded(ServiceId::called('sonarr'), 'thetvdb.com', 'Series metadata')),
    );

    expect($leaving->ours())->toHaveCount(1)
        ->and($leaving->theirs())->toHaveCount(2)
        ->and(iterator_to_array($leaving->theirs(), preserve_keys: false)[0]->service()->named())->toBe('my-fork');
});

it('N10-R12 — nothing leaving is an answer, and a stack that could not be asked is not', function (): void {
    expect(whatWasFoundLeaving(WhatWasFoundLeaving::leaving(WhatLeavesThisMachine::of(OurRequests::of(), TheirRequests::of()))))->toBe('leaving:0/0')
        ->and(whatWasFoundLeaving(WhatWasFoundLeaving::met(Obstacle::StackDidNotAnswer)))
        ->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
