<?php

declare(strict_types=1);

use Lemonfiber\Native\Artwork;
use Native\Mobile\Testing\FakeBridge;

// The artwork capability's PHP face, driven through the real bridge call.
//
// The bridge names are written out as literals for the reason `LinkTest` gives:
// a test spelling them through `Call` would agree with a wrong enum.

const A_PICTURES_KEY = 'lemonfiber-art:0123456789abcdef0123456789abcdef';

beforeEach(function (): void {
    FakeBridge::disable();
});

it('hands a picture over as base64 under its key and its stack, and reads it as held', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Artwork.Hold', ['outcome' => 'in-memory']);

    expect(new Artwork()->hold(A_PICTURES_KEY, 'attic', "\x89PNG"))->toBeTrue();

    $bridge->assertCalled(
        'Lemonfiber.Artwork.Hold',
        static fn(array $sent): bool => $sent === ['key' => A_PICTURES_KEY, 'stack' => 'attic', 'bytes' => 'iVBORw=='],
    );
});

it('reads a refusal, and no answer at all, as not held', function (): void {
    FakeBridge::enable()->respondTo('Lemonfiber.Artwork.Hold', ['outcome' => 'refused', 'because' => 'not-a-picture']);

    expect(new Artwork()->hold(A_PICTURES_KEY, 'attic', 'x'))->toBeFalse();

    FakeBridge::disable();
    FakeBridge::enable();

    expect(new Artwork()->hold(A_PICTURES_KEY, 'attic', 'x'))->toBeFalse();
});

it('asks whether a key is held, and reads anything but yes as no', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Artwork.Holds', ['outcome' => 'in-memory', 'holds' => true]);

    expect(new Artwork()->holds(A_PICTURES_KEY))->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Artwork.Holds', static fn(array $sent): bool => $sent === ['key' => A_PICTURES_KEY]);

    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Artwork.Holds', ['outcome' => 'in-memory', 'holds' => 'yes']);

    expect(new Artwork()->holds(A_PICTURES_KEY))->toBeFalse();
});

it('lets go of a stack\'s pictures', function (): void {
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Artwork.LetGo', ['outcome' => 'let-go']);

    expect(new Artwork()->letGoOf('attic'))->toBeTrue();

    $bridge->assertCalled('Lemonfiber.Artwork.LetGo', static fn(array $sent): bool => $sent === ['stack' => 'attic']);

    FakeBridge::disable();
    FakeBridge::enable();

    expect(new Artwork()->letGoOf('attic'))->toBeFalse();
});
