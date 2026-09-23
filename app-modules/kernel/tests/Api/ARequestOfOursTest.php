<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ARequestOfOurs;
use Modules\Kernel\Api\RequestSaysNothing;
use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhereItGoes;
use Modules\Kernel\Api\WhetherItIsAllowed;

use function sprintf;

/**
 * The update check, with any one word replaced.
 *
 * @param array<string, string> $instead
 * @param list<string>          $destinations
 */
function theUpdateCheck(array $instead = [], array $destinations = ['api.github.com'], WhetherItIsAllowed $allowed = WhetherItIsAllowed::Allowed): ARequestOfOurs
{
    $words = [...[
        'purpose' => 'To say when a newer lemonfiber is out',
        'sends' => 'Nothing but the request itself',
        'switch' => 'updates.check',
        'cost' => 'Nobody hears that a version came out',
    ], ...$instead];

    return ARequestOfOurs::described(
        WhatLemonfiberAsksFor::Updates,
        WhereItGoes::to(...$destinations),
        $words['purpose'],
        $words['sends'],
        $allowed,
        $words['switch'],
        $words['cost'],
    );
}

it('N10-R2, N10-R3 — carries its purpose, destinations, what it sends, its switch and what turning it off costs', function (): void {
    $request = theUpdateCheck(destinations: ['api.github.com', 'objects.githubusercontent.com']);

    expect($request->asksFor())->toBe(WhatLemonfiberAsksFor::Updates)
        ->and(iterator_to_array($request->destinations(), preserve_keys: false))->toBe(['api.github.com', 'objects.githubusercontent.com'])
        ->and($request->purpose())->toBe('To say when a newer lemonfiber is out')
        ->and($request->sends())->toBe('Nothing but the request itself')
        ->and($request->allowed())->toBe(WhetherItIsAllowed::Allowed)
        ->and($request->switch())->toBe('updates.check')
        ->and($request->cost())->toBe('Nobody hears that a version came out');
});

it('may be configured to reach nothing, which is not the same as switched off', function (): void {
    $request = theUpdateCheck(destinations: []);

    expect($request->destinations())->toHaveCount(0)
        ->and($request->allowed())->toBe(WhetherItIsAllowed::Allowed);
});

it('refuses a request with any word blank, naming which', function (string $field): void {
    // A cost left blank reads as *nothing breaks*, and the others are the
    // same argument about a different question.
    expect(fn(): ARequestOfOurs => theUpdateCheck([$field => ' ']))
        ->toThrow(RequestSaysNothing::class, sprintf('`%s`', $field));
})->with(['purpose', 'sends', 'switch', 'cost']);

it('refuses a blank destination among real ones', function (): void {
    expect(fn(): ARequestOfOurs => theUpdateCheck(destinations: ['api.github.com', '  ']))
        ->toThrow(RequestSaysNothing::class, '`destination`');
});
