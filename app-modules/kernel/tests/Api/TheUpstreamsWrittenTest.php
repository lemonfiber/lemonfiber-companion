<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAdapterWritingItsUpstream;
use Modules\Kernel\Api\TheUpstreamsWritten;

it('holds each adapter in the stack\'s order, and none where none was stated', function (): void {
    $first = AnAdapterWritingItsUpstream::by('homepage-adapter', 'homepage', '/upstream');
    $second = AnAdapterWritingItsUpstream::by('seerr-adapter', 'seerr', '/config');

    expect(iterator_to_array(TheUpstreamsWritten::these($first, $second), preserve_keys: false))->toBe([$first, $second])
        ->and(iterator_to_array(TheUpstreamsWritten::these(), preserve_keys: false))->toBe([]);
});
