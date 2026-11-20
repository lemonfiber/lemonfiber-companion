<?php

declare(strict_types=1);

use Lemonfiber\Native\Pages;
use Native\Mobile\Testing\FakeBridge;

beforeEach(function (): void {
    FakeBridge::disable();
});

it('carries the address and reads an opened answer as the page having opened', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Page.Open', ['outcome' => 'opened']);

    expect(new Pages()->open('https://approve.example/link'))->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Page.Open', static fn(mixed $params): bool => $params === ['address' => 'https://approve.example/link']);
});

it('reads a refusal, a word it does not know, or no answer as the page not having opened', function (array $answered): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Page.Open', $answered);

    expect(new Pages()->open('https://approve.example/link'))->toBeFalse();
})->with([
    'refused' => [['outcome' => 'refused']],
    'a word it does not know' => [['outcome' => 'perhaps']],
    'no outcome' => [[]],
]);

it('reads no bridge at all as the page not having opened', function (): void {
    expect(new Pages()->open('https://approve.example/link'))->toBeFalse();
});
