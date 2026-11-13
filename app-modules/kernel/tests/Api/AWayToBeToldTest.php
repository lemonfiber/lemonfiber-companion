<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWayToBeTold;
use Modules\Kernel\Api\AWayToBeToldSaysNothing;
use Modules\Kernel\Api\WaysToBeTold;

it('holds a way by its id, its name trimmed, and whether the member is told on it', function (): void {
    $way = AWayToBeTold::offered('mail', ' Email ', receiving: true);

    expect([$way->id(), $way->name(), $way->receiving()])->toBe(['mail', 'Email', true]);
});

it('refuses a way with no id to choose it by, or no name to read', function (string $id, string $name, string $said): void {
    expect(static fn(): AWayToBeTold => AWayToBeTold::offered($id, $name, receiving: false))->toThrow(AWayToBeToldSaysNothing::class, $said);
})->with([
    'a blank id' => [' ', 'Email', 'no id'],
    'a blank name' => ['mail', ' ', 'no name'],
]);

it('says whether anything is offered and whether this phone is woken', function (): void {
    expect(WaysToBeTold::offered(phone: true, ways: [])->isEmpty())->toBeTrue()
        ->and(WaysToBeTold::offered(phone: true, ways: [])->wakesThisPhone())->toBeTrue()
        ->and(WaysToBeTold::offered(phone: false, ways: [AWayToBeTold::offered('mail', 'Email', receiving: false)])->isEmpty())->toBeFalse()
        ->and(WaysToBeTold::offered(phone: false, ways: [])->wakesThisPhone())->toBeFalse();
});
