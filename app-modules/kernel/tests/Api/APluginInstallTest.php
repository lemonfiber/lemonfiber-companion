<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\AProof;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheContestsLeft;
use Modules\Kernel\Api\ThePluginChanges;
use Modules\Kernel\Api\TheProofs;
use Modules\Kernel\Api\TheSettingsItOverrides;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatTheChecksMade;
use Tests\Support\APluginAsItArrives;

/** An install recorded, with this proof and these checks. */
function anInstallRecorded(WhatAProofCameTo $cameTo, WhatTheChecksMade $checks, bool $recorded = true): APluginInstall
{
    return APluginInstall::reported(
        APluginAsItArrives::held(),
        recorded: $recorded,
        changes: ThePluginChanges::these(),
        proofs: TheProofs::these(AProof::of('answers', 'It answers', 'GET /', '', $cameTo)),
        contests: TheContestsLeft::these(),
        overrides: TheSettingsItOverrides::these(),
        checks: $checks,
    );
}

it('is installed only where it was recorded, no proof failed and the checks found nothing broken', function (): void {
    $nothingBroke = WhatTheChecksMade::of(PluginLines::none(), PluginLines::under('unsettled', 'The VPN'));

    expect(anInstallRecorded(WhatAProofCameTo::passed(), $nothingBroke)->held())->toBeTrue()
        ->and(anInstallRecorded(WhatAProofCameTo::unproven('The service did not settle'), $nothingBroke)->held())->toBeTrue()
        ->and(anInstallRecorded(WhatAProofCameTo::failed(PluginLines::under('faults', 'It answered 502')), $nothingBroke)->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), WhatTheChecksMade::of(PluginLines::under('broke', 'Sonarr answers'), PluginLines::none()))->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), WhatTheChecksMade::notAsked())->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), $nothingBroke, recorded: false)->held())->toBeFalse();
});

it('is a reading only where nothing was recorded and nothing put back', function (): void {
    $reading = APluginAsItArrives::theReading();
    $putBack = APluginAsItArrives::thePutBack();
    $installed = APluginAsItArrives::theInstall();

    expect($reading->agreement())->toBe(APluginAsItArrives::AGREEMENT)
        ->and($putBack->agreement())->toBe('')
        ->and($installed->agreement())->toBe('')
        ->and(APluginAsItArrives::theListing()->agreement())->toBe('')
        ->and(APluginAsItArrives::theListing()->approvals()->isEmpty())->toBeTrue()
        ->and($reading->approvals()->count())->toBe(1);
});

it('says the checks were not asked apart from asked and finding nothing', function (): void {
    expect(WhatTheChecksMade::notAsked()->wereAsked())->toBeFalse()
        ->and(WhatTheChecksMade::notAsked()->brokeNothing())->toBeFalse()
        ->and(WhatTheChecksMade::of(PluginLines::none(), PluginLines::none())->brokeNothing())->toBeTrue();
});
