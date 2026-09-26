<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\KeepingSaysNothing;

it('carries both roots as the stack wrote them', function (): void {
    $moved = ARelocation::from('/srv/old', '/srv/new');

    expect($moved->was())->toBe('/srv/old')
        ->and($moved->now())->toBe('/srv/new');
});

it('refuses either root left blank, naming which', function (string $was, string $now, string $named): void {
    expect(fn(): ARelocation => ARelocation::from($was, $now))->toThrow(KeepingSaysNothing::class, $named);
})->with([
    'where it was' => [' ', '/srv/new', '`was`'],
    'where it goes' => ['/srv/old', ' ', '`now`'],
]);
