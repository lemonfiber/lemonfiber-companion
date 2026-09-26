<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WireField;
use Modules\Sdk\Internal\WhatAQualityAnswerCarries;

it('refuses a format whose word is only spaces, before anything is built from it', function (): void {
    $format = ['scope' => 'music', 'format' => ' ', 'means' => 'm', 'targets' => 'FLAC', 'size_per_hour' => '~300 MB', 'note' => 'n'];

    expect(static fn(): mixed => WhatAQualityAnswerCarries::format($format, 'music', WireField::Format))
        ->toThrow(QualityIsUnreadable::class, 'The music envelope has no readable `format.format`');
});

it('refuses a failure whose reason is empty, before anything is built from it', function (): void {
    expect(static fn(): mixed => WhatAQualityAnswerCarries::asking(['outcome' => ['state' => 'failed', 'detail' => '']], 'upgrade'))
        ->toThrow(QualityIsUnreadable::class, 'The upgrade envelope has no readable `outcome.detail`');
});
