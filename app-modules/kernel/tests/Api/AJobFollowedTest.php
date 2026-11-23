<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AJobFollowed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\WhatWasHeardFromAJob;

it('runs and has said nothing when it is first followed', function (): void {
    $followed = AJobFollowed::named(Job::named('copy-the-stack'));

    expect($followed->job->shown())->toBe('copy-the-stack')
        ->and($followed->running)->toBeTrue()
        ->and($followed->said->lines)->toBe([]);
});

it('keeps what it said when its standing changes, and its standing when it says more', function (): void {
    $followed = AJobFollowed::named(Job::named('copy-the-stack'))->heard(WhatWasHeardFromAJob::said('Writing the archive'))->finished()->heard(WhatWasHeardFromAJob::said('Done'));

    expect($followed->running)->toBeFalse()
        ->and($followed->said->lines)->toBe(['Writing the archive', 'Done'])
        ->and($followed->job->is(Job::named('copy-the-stack')))->toBeTrue();
});
