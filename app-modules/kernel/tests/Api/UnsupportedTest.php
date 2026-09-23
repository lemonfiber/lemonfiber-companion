<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALimitSaysNothing;
use Modules\Kernel\Api\Unsupported;

it('carries what the stack cannot act on, and why', function (): void {
    $limit = Unsupported::of('sabnzbd', 'lemonfiber does not manage this download client');

    expect($limit->what())->toBe('sabnzbd')
        ->and($limit->because())->toBe('lemonfiber does not manage this download client');
});

it('trims both halves', function (): void {
    $limit = Unsupported::of("  sabnzbd\n", '  not managed here  ');

    expect($limit->what())->toBe('sabnzbd')
        ->and($limit->because())->toBe('not managed here');
});

it('refuses a limit about nothing', function (): void {
    // *There is something here lemonfiber cannot help with* is not an answer an
    // operator can act on.
    expect(static fn(): Unsupported => Unsupported::of('   ', 'not managed here'))
        ->toThrow(ALimitSaysNothing::class);
});

it('refuses a limit with no reason', function (): void {
    // Worse than the one above: a limit without a reason reads as a fault, and
    // the whole point of this value is that a limitation is not one.
    expect(static fn(): Unsupported => Unsupported::of('sabnzbd', "  \n "))
        ->toThrow(ALimitSaysNothing::class);
});
