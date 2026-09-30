<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\InTheConnectionCatalogue;

it('names what happened by the stem in the connection group', function (): void {
    expect(InTheConnectionCatalogue::under('no_answer')->said())->toBe('connection.no_answer');
});

it('names what to do about it by the stem with its remedy suffix', function (): void {
    expect(InTheConnectionCatalogue::under('no_answer')->remedy())->toBe('connection.no_answer_action');
});
