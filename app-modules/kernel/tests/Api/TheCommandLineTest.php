<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\TheCommandLine;

it('says the command as the stack\'s terminal prints it, the words joined by spaces', function (): void {
    expect(TheCommandLine::of('docker', 'compose', '--project-name', 'media', 'up', '-d')->asTyped())
        ->toBe('docker compose --project-name media up -d');
});

it('says a command of one word as that word', function (): void {
    expect(TheCommandLine::of('true')->asTyped())->toBe('true');
});
