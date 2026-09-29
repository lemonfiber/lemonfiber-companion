<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\StopSeedingIsUnreadable;

it('names the field a stop-seeding answer left out', function (): void {
    expect(StopSeedingIsUnreadable::missing('download.standing')->getMessage())
        ->toBe('The stop-seeding envelope has no readable `download.standing`. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names every word it reads, each as a word, when refusing one it does not', function (): void {
    expect(StopSeedingIsUnreadable::word('download.standing', 'paused', 'seeding', 'complete')->getMessage())
        ->toBe('The stop-seeding envelope says `download.standing` is `paused`, and this app reads `seeding`, `complete`. Drawing it as the nearest one would be a guess about what letting it go costs.');
});
