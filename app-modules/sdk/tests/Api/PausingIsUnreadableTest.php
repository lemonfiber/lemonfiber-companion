<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\PausingIsUnreadable;

it('names the field a pausing answer left out', function (): void {
    expect(PausingIsUnreadable::missing('clients.0.client')->getMessage())
        ->toBe('The pausing envelope has no readable `clients.0.client`. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names every word it reads, each as a word, when refusing one it does not', function (): void {
    expect(PausingIsUnreadable::word('clients.0.was', 'seeding', 'fetching', 'stopped')->getMessage())
        ->toBe('The pausing envelope says `clients.0.was` is `seeding`, and this app reads `fetching`, `stopped`. Drawing it as the nearest one would be a guess about what the downloads are doing.');
});
