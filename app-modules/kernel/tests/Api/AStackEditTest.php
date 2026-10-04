<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AStackEdit;

it('writes its diff back with the marks the stack gave it, line for line', function (): void {
    $diff = "- PUID=1001\n+ PUID=1000\n- \n+ TZ=Europe/Amsterdam";

    expect(AStackEdit::at('compose.yaml', $diff)->diff())->toBe($diff)
        ->and(AStackEdit::at('compose.yaml', AStackEdit::at('compose.yaml', $diff)->diff())->count())->toBe(4);
});

it('writes no diff where no line can show the difference', function (): void {
    expect(AStackEdit::at('compose.yaml', '')->diff())->toBe('');
});
