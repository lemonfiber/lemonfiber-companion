<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Permission;

use function sprintf;

it('N4-R3 — every permission has a working alternative when it is declined', function (): void {
    // Where "working alternative" stops being a promise in a document. A case
    // answering false would be a permission this app cannot honestly call
    // optional, and the test is here so that adding one is a decision taken in
    // front of the alternative rather than by writing a new case.
    $without = [];

    foreach (Permission::cases() as $permission) {
        if (! $permission->hasAnAlternative()) {
            $without[] = $permission->value;
        }
    }

    expect($without)->toBe([], sprintf(
        "These permissions have no alternative when declined:\n  %s\n\n"
        . 'N4-R3 makes every permission optional and requires a working alternative for '
        . 'each declined one. A pairing code can be typed instead of scanned, the app '
        . 'can be opened to see what a notification would have said, and a stack can be '
        . 'reached over a route the platform does not gate — each case here needs an '
        . 'answer of that kind before it can be added.',
        implode("\n  ", $without),
    ));
});

it('N4-R1 — the app names what it asks for, and asks for nothing else', function (): void {
    // A closed set is the point: a permission the app has no use for is one it
    // must not request, and each request goes at its own point of first
    // use — so every case here has exactly one place that asks.
    expect(Permission::cases())->toBe([
        Permission::LocalNetwork,
        Permission::Notifications,
        Permission::Camera,
    ]);
});
