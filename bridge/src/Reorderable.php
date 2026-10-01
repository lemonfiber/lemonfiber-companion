<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function explode;
use function implode;
use function is_array;
use function is_string;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Override;

/**
 * A list somebody puts in order by dragging its rows, drawn natively.
 *
 * Kotlin draws it with Compose and Swift with SwiftUI (`ReorderableRenderer`
 * on each). Each row carries a key, the name it shows, and the two sentences a
 * screen reader offers as actions to move it one place up or down, so the list
 * can be put in order without dragging at all.
 *
 * When a row lands somewhere new, the list sends its keys in their new order as
 * one value, written by {@see sent()} on the native side's terms and read back
 * by {@see keysIn()}. The screen decides what the keys mean; this carries them.
 */
final class Reorderable extends Element
{
    /** Between two keys in the order a list sends: no key holds a line break. */
    public const string BETWEEN = "\n";

    /** The element's type, which the manifest and the Blade tag name too. */
    public const string TYPE = 'lemonfiber_reorderable';

    #[Override]
    protected string $type = self::TYPE;

    /** @var array{keys: list<string>, names: list<string>, move_up: list<string>, move_down: list<string>} */
    private array $rows = ['keys' => [], 'names' => [], 'move_up' => [], 'move_down' => []];

    private ?string $changeCallback = null;

    public static function make(): self
    {
        return new self();
    }

    /**
     * The keys an order a list sent holds, in that order.
     *
     * @return list<string>
     */
    public static function keysIn(string $sent): array
    {
        return $sent === '' ? [] : explode(self::BETWEEN, $sent);
    }

    /**
     * Keys as a list sends them.
     *
     * @param list<string> $keys
     */
    public static function sent(array $keys): string
    {
        return implode(self::BETWEEN, $keys);
    }

    /** @param array<mixed> $attrs */
    #[Override]
    public function applyAttributes(array $attrs): void
    {
        $items = array_key_exists('items', $attrs) ? $attrs['items'] : [];

        foreach (is_array($items) ? $items : [] as $item) {
            $this->row($item);
        }

        $this->applyA11yAttributes($attrs);
    }

    /** One row: its key, the name it shows, and what a screen reader offers to move it. */
    public function item(string $key, string $name, string $moveUp, string $moveDown): self
    {
        $this->rows['keys'][] = $key;
        $this->rows['names'][] = $name;
        $this->rows['move_up'][] = $moveUp;
        $this->rows['move_down'][] = $moveDown;

        return $this;
    }

    /** The screen's method that hears the new order, as {@see sent()} writes it. */
    public function onChange(string $method): self
    {
        $this->changeCallback = $method;

        return $this;
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = $this->rows;

        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

        return $props;
    }

    /** A row from the template, where it names all four of its parts as text. */
    private function row(mixed $item): void
    {
        if (! is_array($item)) {
            return;
        }

        $key = $this->text($item, 'key');
        $name = $this->text($item, 'name');
        $up = $this->text($item, 'up');
        $down = $this->text($item, 'down');

        if ($key === null || $name === null || $up === null || $down === null) {
            return;
        }

        $this->item($key, $name, $up, $down);
    }

    /**
     * One named part of a row, where it is text.
     *
     * @param array<mixed> $item
     */
    private function text(array $item, string $part): ?string
    {
        if (! array_key_exists($part, $item) || ! is_string($item[$part])) {
            return null;
        }

        return $item[$part];
    }
}
