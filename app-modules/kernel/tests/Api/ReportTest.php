<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;

it('carries the word and the findings together', function (): void {
    $findings = Findings::of(
        Finding::of(Check::of('vpn.egress-match'), Category::Vpn, 'Egress', Conclusion::Failed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled()),
    );

    $report = Report::of(Overall::Broken, $findings);

    expect($report->overall())->toBe(Overall::Broken);
    expect($report->findings())->toBe($findings);
});

it('takes a healthy run with nothing to report', function (): void {
    // The outcome the product is for, and it is a report rather than an absent
    // one — `Findings::none()` says it without a null anywhere.
    $report = Report::of(Overall::Healthy, Findings::none());

    expect($report->overall())->toBe(Overall::Healthy);
    expect($report->findings()->count())->toBe(0);
});

it('does not argue with the engine about its own verdict', function (): void {
    // A `Healthy` report holding a failure is the engine contradicting itself.
    // This side inventing a rule about which half to believe would be a second
    // opinion about somebody else's data, wrong in a way nobody could see from
    // the screen — so it is carried as sent and is a bug where it was decided.
    $report = Report::of(Overall::Healthy, Findings::of(
        Finding::of(Check::of('storage.room'), Category::Storage, 'Room', Conclusion::Failed, WhatTheCheckSaid::nothingWrong(), WhoPutItThere::bundled()),
    ));

    expect($report->overall())->toBe(Overall::Healthy);
    expect($report->findings()->count())->toBe(1);
});
