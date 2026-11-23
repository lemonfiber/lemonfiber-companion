<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheJobsSaid;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

use function sprintf;

/** Which arm what one job was heard to say took, and what it carried there. */
function whatTheReadingGave(WhatWasHeardFromAJob $heard): string
{
    return $heard->either(
        said: static fn(string ...$lines): Code => Code::of(sprintf('said %s', implode(' | ', $lines))),
        nothingNew: static fn(): Code => Code::of('nothing new'),
        met: static fn(Obstacle $why): Code => Code::of(sprintf('met %s', $why->kind()->value)),
    )->shown();
}

it('gives each job the lines it said in the reading, and nothing new to a job that said none', function (): void {
    $heard = WhatTheJobsSaid::nothingNew()
        ->with(Job::named('job-landing'), WhatWasHeardFromAJob::said('Waiting on two downloads'))
        ->with(Job::named('job-rehearsal'), WhatWasHeardFromAJob::said('Reading the settings', 'Rehearsed'));

    expect(whatTheReadingGave($heard->from(Job::named('job-landing'))))->toBe('said Waiting on two downloads')
        ->and(whatTheReadingGave($heard->from(Job::named('job-rehearsal'))))->toBe('said Reading the settings | Rehearsed')
        ->and(whatTheReadingGave($heard->from(Job::named('job-nobody-asked-about'))))->toBe('nothing new');
});

it('says what stood in the way to every job, where the stream could not be heard', function (): void {
    $heard = WhatTheJobsSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));

    expect(whatTheReadingGave($heard->from(Job::named('job-landing'))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value))
        ->and(whatTheReadingGave($heard->from(Job::named('job-rehearsal'))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('keeps the reading it was given, adding a job to a new one', function (): void {
    $before = WhatTheJobsSaid::nothingNew();
    $before->with(Job::named('job-landing'), WhatWasHeardFromAJob::said('Waiting on two downloads'));

    expect(whatTheReadingGave($before->from(Job::named('job-landing'))))->toBe('nothing new');
});

it('hands over the reading where the stream was heard, and what stood in the way where it was not', function (): void {
    $heard = WhatTheJobsSaid::nothingNew()->with(Job::named('job-landing'), WhatWasHeardFromAJob::said('Waiting on two downloads'));
    $read = static fn(WhatTheJobsSaid $reading): Code => Code::of(whatTheReadingGave($reading->from(Job::named('job-landing'))));
    $stood = static fn(Obstacle $why): Code => Code::of(sprintf('met %s', $why->kind()->value));

    expect($heard->either(heard: $read, met: $stood)->shown())->toBe('said Waiting on two downloads')
        ->and(WhatTheJobsSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->either(heard: $read, met: $stood)->shown())
        ->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});
