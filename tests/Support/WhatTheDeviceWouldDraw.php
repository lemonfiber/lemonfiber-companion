<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;

use Closure;

use function in_array;
use function is_array;
use function is_string;

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\UI\Builders\Drawer;
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
 * an unpadded screen. There is the same shape of question again: *nothing
 * drawn behind an engaged lock* is a claim about a rendered tree, and a rule reading
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

    /** The prop a list row carries its first line on. */
    private const string HEADLINE = 'headline';

    /**
     * The props a list row carries its other lines on, each drawn as a line of
     * its own after the headline.
     */
    private const array LINES_BESIDE = ['supporting', 'trailing_value'];

    /**
     * The prop a control with no visible words carries its name on.
     *
     * A tappable row says what it is *showing* and not what tapping it does, so
     * the only name it has for a reader is the one `F5` insists on. Read last,
     * after the two visible props: where a control has both, what is drawn is
     * what somebody sees and is the better answer to *what does this frame
     * say*.
     *
     * Underscored, because that is the prop the collector writes — the markup
     * spells it `a11y-label` and the tree carries `a11y_label`, and reading the
     * markup's spelling here finds nothing at all.
     */
    private const string NAMED_FOR_A_READER = 'a11y_label';

    /**
     * Every kind of node a person operates whatever it holds.
     *
     * A button, a tappable row and a chip. A list row is operated only when it
     * is given something to do, which {@see isOperated()} reads off the node.
     */
    private const array OPERATED = ['button', 'pressable', 'chip', 'toggle'];

    /** The list row, a control only when something handles its press. */
    private const string LIST_ROW = 'list_item';

    /** Where a screen's children hang. */
    private const string BENEATH = 'children';

    /**
     * @param list<array{type: string, said: string, operated: bool}> $nodes every line that is said
     */
    private function __construct(private array $nodes) {}

    /** Render this screen the way the device renders it. */
    public static function by(NativeComponent $screen): self
    {
        return new self(self::collect(self::tree($screen)));
    }

    /**
     * The frame after this screen's first, the way the device renders it.
     *
     * A frame reads a stack once, so what a screen reads second is on this
     * frame and not the first.
     */
    public static function onTheSecondFrame(NativeComponent $screen): self
    {
        self::by($screen);

        return self::by($screen);
    }

    /**
     * The tree this screen hands the device, whole: types, layouts and props,
     * for what the words on it leave out.
     *
     * @return array<array-key, mixed>
     */
    public static function tree(NativeComponent $screen): array
    {
        return self::treeMadeBy($screen, $screen->render(...));
    }

    /**
     * The frame a screen draws before its `mount()` runs, the way the device draws it.
     *
     * A `#[Lazy]` screen publishes this first, and it is the frame an operator
     * sees while the stack is asked anything.
     */
    public static function whileItOpens(NativeComponent $screen): self
    {
        // The placeholder may draw the frame's chrome with it, and the side
        // menu is rendered against the screen's callbacks while it does.
        $reflected = new ReflectionObject($screen);
        $reflected->getProperty('nativeCallbacks')->setValue($screen, new CallbackRegistry());

        return new self(self::collect(self::treeMadeBy(
            $screen,
            $reflected->getMethod('placeholder')->getClosure($screen),
        )));
    }

    /**
     * What a screen's side menu draws, rendered against the screen as the phone renders it.
     *
     * The menu is not part of the screen's own view: NativePHP asks the screen
     * for it and draws it beside the frame, so a test that read only the frame
     * would never see it.
     */
    public static function inTheMenu(NativeComponent $screen, Drawer $menu): self
    {
        return new self(self::collect(self::treeMadeBy(
            $screen,
            static fn(): Element => TheWaysAround::drawnOn($screen, $menu),
        )));
    }

    /**
     * What the list of stacks draws on a screen about a stack: the name in the
     * top bar that opens it, then the sheet, which draws its rows only while it
     * is open.
     */
    public static function inTheListOfStacks(NativeComponent $screen): self
    {
        return new self(self::collect([self::BENEATH => TheWaysAround::theListOfStacksIn(self::tree($screen))]));
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
            if ($node['operated']) {
                $controls[] = $node['said'];
            }
        }

        return $controls;
    }

    /**
     * The tree one of a screen's frames hands the device, drawn as the device draws it.
     *
     * @param Closure(): mixed $making what the screen answers with for the frame: its render, or its placeholder
     *
     * @return array<array-key, mixed>
     */
    private static function treeMadeBy(NativeComponent $screen, Closure $making): array
    {
        $was = NativeTagPrecompiler::setActive(active: true);

        try {
            $tree = self::rendered($screen, $making());
        } finally {
            NativeTagPrecompiler::setActive(active: $was);
        }

        $id = 1;
        $emitted = [];
        $hashes = [];

        return $tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes);
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
    private static function rendered(NativeComponent $screen, mixed $made): Element
    {
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
     * @return list<array{type: string, said: string, operated: bool}>
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
     * A list row says its headline and then each line beside it, and only its
     * headline is the control: the lines after it are what the row shows.
     *
     * @param array<mixed> $node
     *
     * @return list<array{type: string, said: string, operated: bool}>
     */
    private static function itself(array $node): array
    {
        $said = self::wordsIn($node);
        $type = array_key_exists('type', $node) ? $node['type'] : '';

        if ($said === '' || ! is_string($type)) {
            return [];
        }

        $lines = [['type' => $type, 'said' => $said, 'operated' => self::isOperated($type, $node)]];

        foreach (self::linesBeside($node) as $line) {
            $lines[] = ['type' => $type, 'said' => $line, 'operated' => false];
        }

        return $lines;
    }

    /**
     * Whether a person operates this node: always for a button, a tappable row
     * or a chip, and for a list row when something handles its press.
     *
     * @param array<mixed> $node
     */
    private static function isOperated(string $type, array $node): bool
    {
        return in_array($type, self::OPERATED, strict: true)
            || ($type === self::LIST_ROW && array_key_exists('on_press', $node));
    }

    /**
     * A list row's lines after its headline, in the order they are drawn.
     *
     * @param array<mixed> $node
     *
     * @return list<string>
     */
    private static function linesBeside(array $node): array
    {
        $props = array_key_exists('props', $node) ? $node['props'] : [];
        $lines = [];

        foreach (self::LINES_BESIDE as $prop) {
            if (is_array($props) && array_key_exists($prop, $props) && is_string($props[$prop]) && $props[$prop] !== '') {
                $lines[] = $props[$prop];
            }
        }

        return $lines;
    }

    /**
     * What hangs off this node, but the side menu and the list of stacks, and nothing where nothing does.
     *
     * @param array<mixed> $node
     *
     * @return list<mixed>
     */
    private static function beneath(array $node): array
    {
        $children = array_key_exists(self::BENEATH, $node) ? $node[self::BENEATH] : [];

        return is_array($children) ? TheWaysAround::leftOutOf($children) : [];
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

        foreach ([self::SAID, self::LABELLED, self::HEADLINE, self::NAMED_FOR_A_READER] as $prop) {
            if (array_key_exists($prop, $props) && is_string($props[$prop]) && $props[$prop] !== '') {
                return $props[$prop];
            }
        }

        return '';
    }
}
