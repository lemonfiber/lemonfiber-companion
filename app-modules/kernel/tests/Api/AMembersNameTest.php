<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMembersName;
use Modules\Kernel\Api\NobodyWasNamed;

it('hands over the name as typed, without the spaces around it', function (): void {
    expect(AMembersName::of('  ada lovelace ')->forTheExchange())->toBe('ada lovelace');
});

it('refuses a name that names nobody, typed or not', function (string $typed): void {
    expect(fn(): AMembersName => AMembersName::of($typed))
        ->toThrow(NobodyWasNamed::class, 'no name');
})->with(['', '   ']);
