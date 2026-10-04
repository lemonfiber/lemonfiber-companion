<?php

declare(strict_types=1);

namespace Modules\Services\Tests\Api\Queries;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Services\Api\Queries\WhetherItIsInstalled;

/** A service in this state, brought in by these forms. */
function aServiceThat(HowAServiceRuns $runs, string ...$forms): Daemon
{
    $brought = [];

    foreach ($forms as $form) {
        $brought[] = Form::called($form);
    }

    return Daemon::called('Bazarr', ServiceId::called('bazarr'), $runs, HowMuchItMatters::Optional, WhatLeansOnIt::nothing())->broughtInBy(Forms::these(...$brought));
}

it('takes a service the stack runs in any state as installed', function (HowAServiceRuns $runs): void {
    expect(new WhetherItIsInstalled()->of(aServiceThat($runs)))->toBeTrue();
})->with(array_values(array_filter(HowAServiceRuns::cases(), static fn(HowAServiceRuns $runs): bool => $runs !== HowAServiceRuns::Absent)));

it('takes an absent service a form brought in as installed, and one nothing asked for as not', function (): void {
    expect(new WhetherItIsInstalled()->of(aServiceThat(HowAServiceRuns::Absent, 'subtitles')))->toBeTrue()
        ->and(new WhetherItIsInstalled()->of(aServiceThat(HowAServiceRuns::Absent)))->toBeFalse();
});
