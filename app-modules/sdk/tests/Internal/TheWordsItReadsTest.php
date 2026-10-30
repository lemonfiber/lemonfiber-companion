<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Modules\Sdk\Internal\TheWordsItReads;

it('names each word in backticks, joined with commas', function (): void {
    expect(TheWordsItReads::quoted('remove', 'restore', 'keep-copy'))->toBe('`remove`, `restore`, `keep-copy`')
        ->and(TheWordsItReads::quoted())->toBe('');
});
