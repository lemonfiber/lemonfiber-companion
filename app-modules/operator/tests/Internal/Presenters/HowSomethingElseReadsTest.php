<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\SomethingElseRunning;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Kernel\Api\WhatTheEngineCallsIt;
use Modules\Operator\Internal\Presenters\HowSomethingElseReads;
use Modules\Operator\Internal\ViewModels\WhatOneOtherContainerSays;

/**
 * A container nobody declared is drawn with the glyph a service in the same
 * state is, so the two lists read alike.
 */
it('draws each container with the tone of the state it is in', function (): void {
    $running = WhatElseIsRunning::these(
        SomethingElseRunning::called(WhatTheEngineCallsIt::called('pihole'), 'Not declared by this stack', HowAServiceRuns::Running),
        SomethingElseRunning::called(WhatTheEngineCallsIt::called('watchtower'), 'Not declared by this stack', HowAServiceRuns::Starting),
        SomethingElseRunning::called(WhatTheEngineCallsIt::called('old-plex'), 'Not declared by this stack', HowAServiceRuns::CrashLooping),
    );

    $tones = array_map(
        static fn(WhatOneOtherContainerSays $one): string => $one->tone,
        new HowSomethingElseReads()->these($running)->running,
    );

    expect($tones)->toBe([Tone::Fine->value, Tone::Working->value, Tone::Trouble->value]);
});
