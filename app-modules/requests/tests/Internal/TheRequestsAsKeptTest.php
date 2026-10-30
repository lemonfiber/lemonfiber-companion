<?php

declare(strict_types=1);

namespace Modules\Requests\Tests\Internal;

use function expect;
use function it;

use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Modules\Requests\Internal\TheRequestsAsKept;

use function sprintf;

/** A kept reading of one request for a film, standing as written. */
function aReadingStanding(string $standing): Unsealed
{
    return Unsealed::of(sprintf('{"requests":[{"number":1,"by":"sam","for":"A film","size":{},"standing":[%s],"refused":[]}]}', $standing));
}

it('reads a request standing as the stack said it stood', function (): void {
    expect(TheRequestsAsKept::read(Shape::One, aReadingStanding('"declined"')))->toBeInstanceOf(Requested::class);
});

it('reads nothing out of a request whose standing is not one the stack says', function (string $standing): void {
    expect(TheRequestsAsKept::read(Shape::One, aReadingStanding($standing)))->toBeNull();
})->with([
    'a standing that is not text' => ['7'],
    'a standing no stack says' => ['"lost"'],
]);
