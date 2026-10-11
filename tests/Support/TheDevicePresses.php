<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_key_exists;
use function data_get;
use function is_array;
use function is_int;

use Native\Mobile\Edge\NativeComponent;
use ReflectionObject;
use RuntimeException;

use function sprintf;

/**
 * A press on one of a screen's controls, sent the way the device sends it.
 *
 * Through the package's dispatch, which parses the expression the template
 * wrote into a method and its arguments: a test calling the method directly
 * never sees what that expression carries, and a value the stack wrote into
 * it arrives there as whatever the parse made of it.
 */
final readonly class TheDevicePresses
{
    /** Where a control carries the callback its press is sent to: in its props, or on a row itself. */
    private const string PRESSED = 'on_press';

    /** The prop a control carries its name for a reader on. */
    private const string NAMED = 'props.a11y_label';

    /** The kind of event the device sends for a press. */
    private const int PRESS = 1;

    /** Press the control a reader hears as `$named` on the frame this screen draws now. */
    public static function on(NativeComponent $screen, string $named): void
    {
        $pressed = self::pressedOn(WhatTheDeviceWouldDraw::treeThatTakesPresses($screen), $named)
            ?? throw new RuntimeException(sprintf('The frame draws no control named "%s".', $named));

        new ReflectionObject($screen)->getMethod('dispatchUiEvent')->invoke($screen, ['callback_id' => $pressed, 'type' => self::PRESS]);
    }

    /**
     * The callback the first control named `$named` sends its press to, at or under this node.
     *
     * @param array<mixed> $node
     */
    private static function pressedOn(array $node, string $named): ?int
    {
        $pressed = data_get($node, sprintf('props.%s', self::PRESSED), data_get($node, self::PRESSED));

        if (is_int($pressed) && data_get($node, self::NAMED) === $named) {
            return $pressed;
        }

        $children = array_key_exists(WhatTheDeviceWouldDraw::BENEATH, $node) && is_array($node[WhatTheDeviceWouldDraw::BENEATH]) ? TheWaysAround::leftOutOf($node[WhatTheDeviceWouldDraw::BENEATH]) : [];

        foreach ($children as $child) {
            $found = is_array($child) ? self::pressedOn($child, $named) : null;

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
