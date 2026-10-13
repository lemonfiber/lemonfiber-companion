<?php

declare(strict_types=1);

use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WhereAConnectionStands;
use Tests\Support\Fakes\AStackThatWires;
use Tests\Support\WhatTheDeviceWouldDraw;

function whatAdoptingWouldDo(): TheWiring
{
    return TheWiring::rehearsed(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::none(),
        AConnection::of('Sonarr into Jellyfin', WhatItWouldBreak::nothing(), HowAConnectionEnded::plainly(WhereAConnectionStands::WouldAdopt)),
        AConnection::of('SABnzbd into Sonarr', WhatItWouldBreak::nothing(), HowAConnectionEnded::wouldWire('http://sabnzbd:8080', '')),
    );
}

function whatAdoptingDid(): TheWiring
{
    return TheWiring::written(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::none(),
        AConnection::of('Sonarr into Jellyfin', WhatItWouldBreak::nothing(), HowAConnectionEnded::plainly(WhereAConnectionStands::Adopted)),
    );
}

function aStackThatAdopts(): AStackThatWires
{
    return AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('a-rehearsal')),
        WhatBecameOfTheWiring::answered(whatAdoptingWouldDo()),
        WhatBecameOfTheWiring::underway(Job::named('an-adoption')),
        WhatBecameOfTheWiring::answered(whatAdoptingDid()),
    );
}

it('offers seeing what adopting the operator\'s edits would do, and adopts nothing yet', function (): void {
    $wiring = aStackThatAdopts();
    $drawn = WhatTheDeviceWouldDraw::by(theWiringScreen($wiring));

    expect($drawn->said())->toContain(__('stacks.wiring.what_adopting_does'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.rehearse_adopting'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.adopt'))
        ->and($wiring->asked())->toBe([]);
});

it('rehearses adopting, and draws each value kept as theirs and each connection it would wire, labelled as a rehearsal', function (): void {
    $wiring = aStackThatAdopts();
    $screen = theWiringScreen($wiring);
    $screen->rehearseAdopting();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($wiring->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal'])
        ->and($drawn->said())->toContain(__('stacks.wiring.rehearsed'), 'Sonarr into Jellyfin', __('stacks.wiring.state.would_adopt'), 'SABnzbd into Sonarr', __('stacks.wiring.state.would_wire'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.adopt'));
});

it('adopts only against that rehearsal, and draws an adopted value as the operator\'s rather than as drift', function (): void {
    $wiring = aStackThatAdopts();
    $screen = theWiringScreen($wiring);
    $screen->adopt();

    expect($wiring->asked())->toBe([]);

    $screen->rehearseAdopting();
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->adopt();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($wiring->asked())->toBe(['rehearse-adopting', 'after:a-rehearsal', 'adopt', 'after:an-adoption'])
        ->and($drawn->said())->toContain(__('stacks.wiring.state.adopted'))
        ->and($drawn->said())->not->toContain(__('stacks.wiring.state.drifted'), __('stacks.wiring.rehearsed'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.adopt'));
});

it('offers no adopting yes after a wiring run, even one that only rehearsed', function (): void {
    $wiring = AStackThatWires::answering(
        WhatBecameOfTheWiring::underway(Job::named('a-run')),
        WhatBecameOfTheWiring::answered(whatAdoptingWouldDo()),
    );
    $screen = theWiringScreen($wiring);
    $screen->wire();
    $screen->whileItRuns();
    $screen->adopt();

    expect(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(__('stacks.wiring.adopt'))
        ->and($wiring->asked())->toBe(['wire', 'after:a-run']);
});
