<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Internal;

use function expect;
use function it;

use Modules\Connection\Api\TheWayIn;
use Modules\Connection\Internal\TheWayInAsKept;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

it('reads back each way as it was written', function (TheWayIn $way): void {
    expect(TheWayInAsKept::read(Shape::One, TheWayInAsKept::written($way)))->toBe($way);
})->with(TheWayIn::cases());

it('reads what does not read as a way as the password', function (): void {
    expect(TheWayInAsKept::read(Shape::One, Unsealed::of('a-way-a-later-build-wrote')))->toBe(TheWayIn::Password);
});
