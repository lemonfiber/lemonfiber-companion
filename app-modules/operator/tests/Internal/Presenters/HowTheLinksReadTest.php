<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhoSetIt;
use Modules\Operator\Internal\Presenters\HowTheLinksRead;
use Modules\Operator\Internal\ViewModels\AClaimantAsShown;

it('names a contest\'s candidates in the contest\'s order, and draws no origin for one the stack gave none', function (): void {
    $contest = TheLinks::of(WhatNothingFills::none(), ALink::from(ServiceId::called('seerr'), HowItReaches::asked(
        Capability::called('media-server'),
        Services::none(),
        WhatSettledIt::contested(Services::these(ServiceId::called('plex'), ServiceId::called('emby'), ServiceId::called('jellyfin'))),
        TheClaimants::these(
            AClaimant::of(ServiceId::called('jellyfin'), WhoPutItThere::bundled()),
            AClaimant::of(ServiceId::called('plex'), WhoPutItThere::plugin('plex')),
        ),
    )));
    $shown = new HowTheLinksRead()->these($contest)->links[0];

    expect($shown->isContested)->toBeTrue()
        ->and($shown->settledSaid)->toBe('stacks.wiring.fills.contested')
        ->and(array_map(static fn(AClaimantAsShown $claimant): string => $claimant->name, $shown->claimants))->toBe(['plex', 'emby', 'jellyfin'])
        ->and(array_map(static fn(AClaimantAsShown $claimant): ?WhoSetIt => $claimant->from?->came, $shown->claimants))->toBe([WhoSetIt::Plugin, null, WhoSetIt::Bundled]);
});

it('heads a link kept to a named service with the service it runs from, and no capability', function (): void {
    $shown = new HowTheLinksRead()->these(TheLinks::of(
        WhatNothingFills::none(),
        ALink::from(ServiceId::called('jellyfin'), HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs elsewhere')),
    ))->links[0];

    expect([$shown->by, $shown->asks, $shown->settledSaid, $shown->settledWith, $shown->why, $shown->claimants, $shown->isContested])
        ->toBe(['jellyfin', '', 'stacks.wiring.fills.by_name', ['service' => 'tdarr'], 'Transcoding runs elsewhere', [], false]);
});

it('answers a stack that asks nothing, a refusal and an obstacle each as its own', function (): void {
    $nothing = new HowTheLinksRead()->these(TheLinks::of(WhatNothingFills::none()));
    $refused = new HowTheLinksRead()->refused(ARefusalInItsWords::said('It would not read', '', WhatTheRefusalNamed::nothing()));
    $met = new HowTheLinksRead()->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
    $signedOut = new HowTheLinksRead()->signedOut();

    expect([$nothing->went->cameBack(), $nothing->links, $nothing->refused])->toBe([true, [], null])
        ->and($refused->refused?->said)->toBe('It would not read')
        ->and($refused->links)->toBe([])
        ->and($met->went->cameBack())->toBeFalse()
        ->and($met->refused)->toBeNull()
        ->and($signedOut->went->isSignedIn)->toBeFalse();
});
