<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\Fields\UndoField;
use Modules\Sdk\Api\UndoIsUnreadable;
use Modules\Sdk\Api\WireField;

it('names the field an undo report left out', function (): void {
    expect(UndoIsUnreadable::missing(UndoField::Left)->getMessage())
        ->toBe('The undo envelope has no `left`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names the list, the row and the field of a row it could not read', function (): void {
    expect(UndoIsUnreadable::entry(UndoField::Left, WireField::Because, 2)->getMessage())
        ->toBe('Row 2 of `left` in the undo envelope has no readable `because`. It is refused rather than dropped: a report one row short says something went back that did not.');
});

it('names every kind of reversal it reads when refusing one it does not', function (): void {
    expect(UndoIsUnreadable::does('rewind', 1)->getMessage())
        ->toBe('Row 1 of `reversed` in the undo envelope says it does `rewind`, and this app reads `remove`, `restore`, `delete`, `withdraw`, `repin`, `reconfigure`.');
});
