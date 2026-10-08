<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\PluginLines;

it('keeps those also among the others, each once, in its own order', function (): void {
    $approved = PluginLines::under('approved', 'b@here', 'a@there', 'b@here', 'c@nowhere');
    $listed = PluginLines::under('approval', 'a@there', 'b@here');

    expect(iterator_to_array($approved->alsoIn($listed), preserve_keys: false))->toBe(['b@here', 'a@there'])
        ->and($approved->count())->toBe(4)
        ->and(PluginLines::none()->isEmpty())->toBeTrue()
        ->and($listed->isEmpty())->toBeFalse();
});
