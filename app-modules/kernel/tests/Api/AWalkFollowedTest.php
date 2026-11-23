<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWalkFollowed;
use Modules\Kernel\Api\Job;

it('takes a step as its own only where its own job said it', function (): void {
    $walk = AWalkFollowed::named(Job::named('walk-sintel'));

    expect($walk->saidBy(Job::named('walk-sintel')))->toBeTrue()
        ->and($walk->saidBy(Job::named('walk-another')))->toBeFalse();
});

it('takes no step as its own before the stack has named it', function (): void {
    expect(AWalkFollowed::notNamedYet()->saidBy(Job::named('walk-sintel')))->toBeFalse();
});
