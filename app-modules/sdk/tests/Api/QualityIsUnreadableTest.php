<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\QualityIsUnreadable;
use Modules\Sdk\Api\WireField;

it('names every word it reads when refusing one it does not', function (): void {
    expect(QualityIsUnreadable::word('quality', WireField::Disposition, 'mostly', 'shown', 'held')->getMessage())
        ->toBe('The quality envelope says `disposition` is `mostly`, and this app reads `shown`, `held`. Drawing it as the nearest one would be a guess about what became of a choice.');
});
