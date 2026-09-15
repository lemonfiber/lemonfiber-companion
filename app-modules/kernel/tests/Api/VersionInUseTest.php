<?php

declare(strict_types=1);

use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\VersionInUse;

it('carries the version the stack named, and not the release it was read from', function (): void {
    // The narrowing, stated as a value rather than as a type rule. The type
    // rule is `TypesThatMustNotMeetTest`'s and it reads signatures; this reads
    // what actually survives the narrowing, so a `VersionInUse` that carried
    // the release and answered off it would fail here even while its signature
    // still said string.
    $inUse = VersionInUse::of(Release::called('4.0.15', noticeable: false, withdrawn: false));

    expect($inUse->version())->toBe('4.0.15')
        ->and($inUse->wasWithdrawn())->toBeFalse();
});

it('N2-R16 — says the release it is standing on was taken back', function (): void {
    // The opposite errand from the list of what to take next. A withdrawn
    // release is left out of what is offered and said about what is running,
    // and copying the answer across the narrowing is what lets the second half
    // survive a type that cannot reach the first.
    $inUse = VersionInUse::of(Release::called('4.0.17', noticeable: true, withdrawn: true));

    expect($inUse->wasWithdrawn())->toBeTrue()
        ->and($inUse->version())->toBe('4.0.17');
});
