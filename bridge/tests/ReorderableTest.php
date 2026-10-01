<?php

declare(strict_types=1);

use Lemonfiber\Native\Reorderable;
use Lemonfiber\Native\ReorderableTag;
use Native\Mobile\Edge\CallbackRegistry;

// The list put in order by dragging, as PHP hands it to the phone and reads back
// what it sends.

/**
 * The props a list is handed to the phone with.
 *
 * @return array<mixed>
 */
function propsOf(Reorderable $list): array
{
    $drawn = $list->toArray(new CallbackRegistry());

    return is_array($drawn['props'] ?? null) ? $drawn['props'] : [];
}

it('hands the phone each row as its key, its name and its two moves, in order', function (): void {
    $props = propsOf(Reorderable::make()->item('a', 'The loft', 'Move The loft up', 'Move The loft down')->item('b', 'The attic', 'Move The attic up', 'Move The attic down'));

    expect($props['keys'] ?? null)->toBe(['a', 'b'])
        ->and($props['names'] ?? null)->toBe(['The loft', 'The attic'])
        ->and($props['move_up'] ?? null)->toBe(['Move The loft up', 'Move The attic up'])
        ->and($props['move_down'] ?? null)->toBe(['Move The loft down', 'Move The attic down'])
        ->and($props)->not->toHaveKey('on_change');
});

it('hands the phone the callback the screen hears a new order on', function (): void {
    expect(propsOf(Reorderable::make()->onChange('putInOrder'))['on_change'] ?? null)->toBeInt();
});

it('takes its rows from the template, and passes over one that does not name all four parts as text', function (): void {
    $list = Reorderable::make();
    $list->applyAttributes(['items' => [
        ['key' => 'a', 'name' => 'The loft', 'up' => 'Up', 'down' => 'Down'],
        ['key' => 'b', 'name' => 'The attic', 'up' => 'Up'],
        ['key' => 'c', 'name' => 7, 'up' => 'Up', 'down' => 'Down'],
        'not a row',
    ]]);

    expect(propsOf($list)['keys'] ?? null)->toBe(['a']);
});

it('draws no rows where the template names none', function (mixed $items): void {
    $list = Reorderable::make();
    $list->applyAttributes($items === null ? [] : ['items' => $items]);

    expect(propsOf($list)['keys'] ?? null)->toBe([]);
})->with(['no items at all' => [null], 'items that are not a list' => ['three']]);

it('reads back the keys it sent, in order, and nothing from nothing', function (): void {
    expect(Reorderable::keysIn(Reorderable::sent(['a', 'b', 'c'])))->toBe(['a', 'b', 'c'])
        ->and(Reorderable::keysIn(''))->toBe([]);
});

it('names one type in the manifest, the element and its Blade tag', function (): void {
    $manifest = json_decode((string) file_get_contents(sprintf('%s/nativephp.json', dirname(__DIR__))), associative: true);
    $components = is_array($manifest) && is_array($manifest['components'] ?? null) ? $manifest['components'] : [];
    $declared = array_values(array_filter($components, static fn(mixed $one): bool => is_array($one) && ($one['type'] ?? null) === Reorderable::TYPE));
    $tag = new ReflectionMethod(ReorderableTag::class, 'elementType');

    expect($declared)->toHaveCount(1)
        ->and($declared[0]['element'] ?? null)->toBe(Reorderable::class)
        ->and($declared[0]['blade'] ?? null)->toBe(ReorderableTag::class)
        ->and($tag->invoke(new ReorderableTag()))->toBe(Reorderable::TYPE);
});
