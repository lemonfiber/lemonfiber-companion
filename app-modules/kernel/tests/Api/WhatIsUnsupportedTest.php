<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatIsUnsupported;

/** @return list<string> */
function theLimitsNamed(WhatIsUnsupported $limits): array
{
    $named = [];

    foreach ($limits as $limit) {
        $named[] = $limit->what();
    }

    return $named;
}

it('keeps them in the order the stack gave them', function (): void {
    $limits = WhatIsUnsupported::these(
        Unsupported::of('sabnzbd', 'not managed here'),
        Unsupported::of('plex', 'no adapter for this media server'),
    );

    expect(theLimitsNamed($limits))->toBe(['sabnzbd', 'plex'])
        ->and($limits->count())->toBe(2);
});

it('has an empty form, which is the stack saying it can act on everything', function (): void {
    // Not the same as nobody having said. A reading that could not be taken is
    // told apart elsewhere; this is the stack answering, and answering none.
    expect(theLimitsNamed(WhatIsUnsupported::none()))->toBe([])
        ->and(WhatIsUnsupported::none()->count())->toBe(0);
});

it('is a list rather than whatever keys a variadic brought', function (): void {
    // Read off the collection rather than through `theLimitsNamed()`, which
    // appends with `[]=` and so re-keys from zero whatever it was given — it
    // would report a list here with the reindex and without it, which is the
    // one thing this case exists to tell apart.
    $limits = WhatIsUnsupported::these(
        first: Unsupported::of('sabnzbd', 'not managed here'),
        then: Unsupported::of('plex', 'no adapter for this media server'),
    );

    expect(array_keys(iterator_to_array($limits, preserve_keys: true)))->toBe([0, 1])
        ->and($limits->count())->toBe(2);
});
