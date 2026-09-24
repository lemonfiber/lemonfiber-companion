<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhoPutItThere;

it('keeps what the stack is set to in the order the stack listed it', function (): void {
    // The order is the stack's and no sort runs here. An operator reads the
    // same machine through this app and through the stack's own interfaces,
    // and a listing re-ordered on the way would be a second opinion about
    // which settings matter, with nothing on the screen saying why.
    $set = Settings::of(
        Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media'), WhoPutItThere::bundled()),
        Setting::called('API_KEY', WhatASettingHolds::withheld('set, not shown'), WhoPutItThere::bundled()),
        Setting::called('BIND', WhatASettingHolds::shown('lan'), WhoPutItThere::bundled()),
    );

    $named = [];

    foreach ($set as $setting) {
        $named[] = $setting->key;
    }

    expect($named)->toBe(['LIBRARY_PATH', 'API_KEY', 'BIND']);
});

it('has an empty form, which is an answer rather than a missing one', function (): void {
    // A stack with nothing set is an ordinary answer. A screen that draws an
    // empty list silently looks exactly like one that was never given an
    // answer at all, so this type has to be able to say *none* out loud.
    expect(iterator_to_array(Settings::none(), preserve_keys: false))->toBe([])
        ->and(count(Settings::none()))->toBe(0);
});

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and this type
    // publishes itself as holding `int` ones — the template walking it reads
    // the position off the loop. `Settings::of(first: ...)` is a legal call,
    // and so is spreading a keyed array, so the reindexing is load-bearing
    // rather than tidy.
    $set = Settings::of(
        first: Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media'), WhoPutItThere::bundled()),
        then: Setting::called('BIND', WhatASettingHolds::shown('lan'), WhoPutItThere::bundled()),
    );

    expect(array_keys(iterator_to_array($set, preserve_keys: true)))->toBe([0, 1])
        // Counted as well as walked. The two disagree about a collection built
        // with string keys — `count()` reports two while a loop reading the
        // position sees `first` and `then` — and a type that publishes both
        // has to answer the same way to each.
        ->and(count($set))->toBe(2);
});
