<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AClientsHolding;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhereTheLineStands;

/** A line, for a report to carry. */
function aLineAReportCarries(): HowTheLineIsShared
{
    return HowTheLineIsShared::standing(WhereTheLineStands::Overridden, 'The limits are lifted', 'no limit', 'no limit', Remarks::of(), Remarks::of());
}

it('carries the line, what the run did, the lift, and each client in order', function (): void {
    $line = aLineAReportCarries();
    $lift = WhereALiftStands::inForce(60, '');
    $first = AClientsHolding::silent('qbittorrent', WhatADownloadClientSaid::Unsaid, 'no');
    $second = AClientsHolding::silent('sabnzbd', WhatADownloadClientSaid::Unsaid, 'no');
    $report = TheLineAsChanged::said($line, WhatTheLineRunDid::Applied, $lift, $first, $second);

    expect([$report->line(), $report->did(), $report->lift(), $report->hasClients(), iterator_to_array($report, preserve_keys: false)])
        ->toBe([$line, WhatTheLineRunDid::Applied, $lift, true, [$first, $second]]);
});

it('says so where the stack runs no download client', function (): void {
    expect(TheLineAsChanged::said(aLineAReportCarries(), WhatTheLineRunDid::Rehearsed, WhereALiftStands::none())->hasClients())->toBeFalse();
});
