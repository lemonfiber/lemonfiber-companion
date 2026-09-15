<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function is_array;
use function is_string;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeTagPrecompiler;
use ReflectionObject;
use RuntimeException;

/**
 * The element tree a screen puts on the glass, as a thing a test can ask about.
 *
 * **Every other template suite reads Blade as text.** That a screen names its
 * controls, that each is operated, that none addresses the operator in the
 * wrong person — all of it over the characters of the file. That is the right
 * shape for those questions and it cannot answer this one: what a frame *draws*
 * is decided by the precompiler, the component tree and the chrome hoisting,
 * and none of the three is visible in the source. A template carries both arms
 * of its own `@if` and reads as a screen that says everything on every branch.
 *
 * The cost of not being able to ask has already been paid. A padded column
 * rendered empty and last, because slot content is collected before the
 * component's own template runs — every text rule passed and the device showed
 * an unpadded screen. `N4-R24` is the same shape of question: *nothing drawn
 * behind an engaged lock* is a claim about a rendered tree, and a rule reading
 * the template text can see a `@if` and cannot see which arm it produces.
 *
 * **The precompiler has to be switched on.** It transforms `<native:…>` only
 * during a native render, so a plain `Blade::compileString()` is not what the
 * device does and produces a tree with none of these elements in it — which
 * looks like a screen that draws nothing rather than a harness that is not
 * rendering.
 *
 * **It asks the screen for its own view rather than being told the name.** A
 * name passed in is a second spelling of something the screen already decides,
 * and a test naming the wrong template would report on a frame the app never
 * draws.
 */
final readonly class WhatTheDeviceWouldDraw
{
    /** The prop a node carries its words on, whichever kind of node it is. */
    private const string SAID = 'text';

    /** The prop a control carries its words on. */
    private const string LABELLED = 'label';

    /** Where a screen's children hang. */
    private const string BENEATH = 'children';

    /**
     * @param list<array{type: string, said: string}> $nodes every node that says something
     */
    private function __construct(private array $nodes) {}

    /** Render this screen the way the device renders it. */
    public static function by(NativeComponent $screen): self
    {
        $was = NativeTagPrecompiler::setActive(active: true);

        try {
            $tree = self::rendered($screen);
        } finally {
            NativeTagPrecompiler::setActive(active: $was);
        }

        $id = 1;
        $emitted = [];
        $hashes = [];

        return new self(self::collect($tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes)));
    }

    /**
     * Everything the frame says, in the order it is drawn.
     *
     * In order because `F5` cares about the order — a screen is read aloud top
     * to bottom, and a counter drawn after the thing it counts is a surprise
     * rather than an orientation.
     *
     * @return list<string>
     */
    public function said(): array
    {
        $words = [];

        foreach ($this->nodes as $node) {
            $words[] = $node['said'];
        }

        return $words;
    }

    /**
     * Everything the frame offers as a control.
     *
     * Separate from {@see said()} because the two carry different weight: a
     * sentence on a locked frame leaks the shape of somebody's household, and a
     * *button* on one leaks it with something to press.
     *
     * @return list<string>
     */
    public function offers(): array
    {
        $controls = [];

        foreach ($this->nodes as $node) {
            if ($node['type'] === 'button') {
                $controls[] = $node['said'];
            }
        }

        return $controls;
    }

    /**
     * The screen's own render path, which is protected and is the point.
     *
     * A screen may answer with an element or with a view, and the second is the
     * one that needs `fromView()` — the method a screen's own result is fed
     * through on a device. Reaching for it by reflection rather than adding a
     * seam to the package keeps the production path exactly the one the handset
     * takes.
     */
    private static function rendered(NativeComponent $screen): Element
    {
        $made = $screen->render();

        if ($made instanceof Element) {
            return $made;
        }

        $reflected = new ReflectionObject($screen);
        $reflected->getProperty('nativeCallbacks')->setValue($screen, new CallbackRegistry());

        $built = $reflected->getMethod('fromView')->invoke($screen, $made);

        return $built instanceof Element
            ? $built
            : throw new RuntimeException('The screen rendered something that is not an element.');
    }

    /**
     * Walk the tree, keeping every node that says something, in draw order.
     *
     * Returned rather than gathered through a reference parameter, which the
     * analyser refuses and which would be worth avoiding anyway: a caller
     * cannot tell from the signature whether its array is about to be replaced
     * or added to, and getting that backwards is a bug that reads as a screen
     * saying the right things in the wrong order.
     *
     * @return list<array{type: string, said: string}>
     */
    private static function collect(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        $found = self::itself($node);

        foreach (self::beneath($node) as $child) {
            foreach (self::collect($child) as $deeper) {
                $found[] = $deeper;
            }
        }

        return $found;
    }

    /**
     * What this node says on its own, before anything under it.
     *
     * Its own method because the walk crossed the complexity the analyser
     * holds it to, and the split is the one worth making: *what a node is* and
     * *what hangs off it* are two questions, and the recursion is only about
     * the second.
     *
     * @param array<mixed> $node
     *
     * @return list<array{type: string, said: string}>
     */
    private static function itself(array $node): array
    {
        $said = self::wordsIn($node);
        $type = array_key_exists('type', $node) ? $node['type'] : '';

        return $said !== '' && is_string($type) ? [['type' => $type, 'said' => $said]] : [];
    }

    /**
     * What hangs off this node, and nothing where nothing does.
     *
     * @param array<mixed> $node
     *
     * @return array<mixed>
     */
    private static function beneath(array $node): array
    {
        $children = array_key_exists(self::BENEATH, $node) ? $node[self::BENEATH] : [];

        return is_array($children) ? $children : [];
    }

    /**
     * The words a node's props carry, and nothing where it carries none.
     *
     * `array_key_exists` rather than `??` on the subscript, which `C9` refuses
     * for a reason that applies here: a default would say nobody knows whether
     * a node has words, and the two props that hold them are the two this
     * reads.
     *
     * @param array<mixed> $node
     */
    private static function wordsIn(array $node): string
    {
        $props = array_key_exists('props', $node) ? $node['props'] : [];

        if (! is_array($props)) {
            return '';
        }

        foreach ([self::SAID, self::LABELLED] as $prop) {
            if (array_key_exists($prop, $props) && is_string($props[$prop]) && $props[$prop] !== '') {
                return $props[$prop];
            }
        }

        return '';
    }
}
