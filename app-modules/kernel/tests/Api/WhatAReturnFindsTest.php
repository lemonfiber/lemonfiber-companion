<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\WhatAReturnFinds;

use function sprintf;

/**
 * Which arm answered, as a word.
 *
 * Named for this file rather than `fold`, because a module's test files share
 * one namespace and two of the same name are a fatal the moment both load (G10).
 */
function whatTheReturnFound(WhatAReturnFinds $finds): string
{
    return $finds->either(
        job: static fn(Job $job): Code => Code::of(sprintf('follow:%s', $job->shown())),
        nothing: static fn(): Code => Code::of('offer-a-new-one'),
    )->shown();
}

it('hands the job it was given to the arm that follows one', function (): void {
    expect(whatTheReturnFound(WhatAReturnFinds::theJob(Job::named('a-walk-left-running'))))->toBe('follow:a-walk-left-running');
});

it('answers the empty arm where nothing was left', function (): void {
    expect(whatTheReturnFound(WhatAReturnFinds::nothing()))->toBe('offer-a-new-one');
});

it('has no way to reach the handle without saying what happens when there is none', function (): void {
    // A check-then-get pair puts the check where it can be forgotten, and the
    // forgotten one asks a stack after a handle that is not there.
    expect(get_class_methods(WhatAReturnFinds::class))->toBe(['theJob', 'nothing', 'either']);
});
