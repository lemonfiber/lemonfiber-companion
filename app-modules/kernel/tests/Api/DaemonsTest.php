<?php

declare(strict_types=1);

use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatLeansOnIt;

/** What a stack reports its verbs cost, as this suite's stacks report them. */
function whatTheseVerbsCost(): Disturbances
{
    return Disturbances::of(
        starting: WhatItTakesAway::atMost(180),
        stopping: WhatItTakesAway::atMost(10),
        restarting: WhatItTakesAway::atMost(180),
    );
}

/** One service, named so the order can be read back. */
function oneItRuns(string $name, HowAServiceRuns $runs = HowAServiceRuns::Healthy): Daemon
{
    return Daemon::called(
        $name,
        ServiceId::called($name),
        $runs,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    );
}

/** Every service in a listing, in the order it holds them. */
function everythingItRuns(Daemons $daemons): string
{
    $rows = [];

    foreach ($daemons as $one) {
        $rows[] = $one->name();
    }

    return implode(' | ', $rows);
}

it('N2-R7 — holds what the stack runs, in the order it listed them', function (): void {
    // Worst first, which is the contract's order. Sorting alphabetically would
    // put a crashed service under a healthy one, and the operator would scroll
    // past the row they opened the app for.
    $daemons = Daemons::of(
        HowTheStackIsRunning::Degraded,
        Forms::these(Form::called('media')),
        whatTheseVerbsCost(),
        oneItRuns('sonarr', HowAServiceRuns::Failed),
        oneItRuns('radarr'),
    );

    expect(everythingItRuns($daemons))->toBe('sonarr | radarr')
        ->and($daemons->count())->toBe(2);
});

it('carries what the stack says it all amounts to, rather than adding it up', function (): void {
    // A screen adding the services up itself would be a second opinion about
    // something the machine already decided, and the two disagree the first
    // time the machine weighs something differently.
    $daemons = Daemons::of(
        HowTheStackIsRunning::Degraded,
        Forms::none(),
        whatTheseVerbsCost(),
        oneItRuns('sonarr'),
    );

    expect($daemons->running())->toBe(HowTheStackIsRunning::Degraded);
});

it('N2-R7 — carries the forms beside the services, not derived from them', function (): void {
    // A form with nothing running in it still exists, and it is the one an
    // operator most wants: a stack whose whole media form is stopped has a form
    // to start, and a list built from the running services would be missing
    // exactly that one.
    $daemons = Daemons::of(
        HowTheStackIsRunning::Partial,
        Forms::these(Form::called('media'), Form::called('network')),
        whatTheseVerbsCost(),
        oneItRuns('sonarr'),
    );

    $named = [];

    foreach ($daemons->forms() as $form) {
        $named[] = $form->named();
    }

    expect($named)->toBe(['media', 'network']);
});

it('a stack that runs nothing is a state rather than a missing list', function (): void {
    $none = Daemons::none(whatTheseVerbsCost());

    expect($none->count())->toBe(0)
        ->and($none->running())->toBe(HowTheStackIsRunning::Inactive)
        ->and($none->forms()->count())->toBe(0);
});

it('reads by position, whatever keys the variadic arrived with', function (): void {
    // Named arguments give a variadic string keys, and this collection
    // hands its items out again through its iterator — so the keys escape,
    // and everything downstream reads by position. The keys are what has to
    // be read back: `foreach` yields insertion order whatever they are, so
    // a collection that had kept `first` and `second` iterates identically
    // to one that reindexed, and an assertion over what
    // `everythingItRuns()` collects is an assertion about its own
    // accumulator. `Scrollback` and `Stalled` read the keys for the same
    // reason.
    $daemons = Daemons::of(
        running: HowTheStackIsRunning::Active,
        forms: Forms::none(),
        disturbs: whatTheseVerbsCost(),
        first: oneItRuns('sonarr'),
        second: oneItRuns('radarr'),
    );

    expect(array_keys(iterator_to_array($daemons, preserve_keys: true)))->toBe([0, 1]);
});

it('says no form is running and nothing was left out until it is told what was asked', function (): void {
    $daemons = Daemons::none(whatTheseVerbsCost());
    $asked = $daemons->asked(
        Forms::these(Form::called('hunt')),
        TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('hunt')))),
    );

    expect($daemons->active()->count())->toBe(0)
        ->and($daemons->leftOut())->toHaveCount(0)
        ->and($asked->active()->count())->toBe(1)
        ->and($asked->leftOut())->toHaveCount(1)
        ->and([$asked->running(), $asked->count(), $asked->disturbs()])->toBe([$daemons->running(), $daemons->count(), $daemons->disturbs()]);
});
