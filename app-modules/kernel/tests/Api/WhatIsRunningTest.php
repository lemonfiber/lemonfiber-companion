<?php

declare(strict_types=1);

use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatIsRunning;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatLeansOnIt;

/** What a stack reports its verbs cost, as this suite's stacks report them. */
function whatTheRunningVerbsCostHere(): Disturbances
{
    return Disturbances::of(
        starting: WhatItTakesAway::atMost(180),
        stopping: WhatItTakesAway::atMost(10),
        restarting: WhatItTakesAway::atMost(180),
    );
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheListingSaid
{
    public function __construct(public string $said) {}
}

/** Whichever arm an answer takes, as a word. */
function whatCameBackAboutWhatRuns(WhatIsRunning $answer): string
{
    return $answer->either(
        these: static fn(Daemons $daemons): WhatTheListingSaid
            => new WhatTheListingSaid(sprintf('%d running', $daemons->count())),
        met: static fn(Obstacle $why): WhatTheListingSaid => new WhatTheListingSaid($why->value),
    )->said;
}

/** One service, for a listing to hold. */
function aServiceThatIsRunning(): Daemon
{
    return Daemon::called(
        'Sonarr',
        ServiceId::called('sonarr'),
        Form::called('downloads'),
        HowAServiceRuns::Running,
        HowMuchItMatters::Important,
        WhatLeansOnIt::nothing(),
    );
}

it('N2-R7 — a listing takes the arm that renders rows', function (): void {
    $daemons = Daemons::of(
        HowTheStackIsRunning::Active,
        Forms::these(Form::called('downloads')),
        whatTheRunningVerbsCostHere(),
        aServiceThatIsRunning(),
    );

    expect(whatCameBackAboutWhatRuns(WhatIsRunning::these($daemons)))->toBe('1 running');
});

it('a stack running nothing is an answer and not a gap', function (): void {
    // `Inactive` is a state an operator acts on — everything is off, and the
    // thing to do is start something. It must not fold together with *this
    // phone cannot reach the machine*, which looks identical on a screen and
    // means the opposite.
    expect(whatCameBackAboutWhatRuns(WhatIsRunning::these(Daemons::none(whatTheRunningVerbsCostHere()))))->toBe('0 running');
});

it('N1-R10 — an obstacle takes the other arm, carrying which one it was', function (): void {
    foreach (Obstacle::cases() as $why) {
        expect(whatCameBackAboutWhatRuns(WhatIsRunning::met($why)))->toBe($why->value);
    }
});
