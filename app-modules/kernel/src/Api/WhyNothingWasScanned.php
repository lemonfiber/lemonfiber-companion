<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why the camera came back without a pairing code.
 *
 * `N1-R10`'s habit applied to a scanner: three ways to get nothing, and three
 * different things for the operator to do about it. One sentence for all of
 * them is the sentence that is unhelpful for whichever they are actually in.
 *
 * **`N4-R3` is why the middle case cannot be a dead end.** A declined camera is
 * a permission this app may not have, and it promises a working alternative for
 * every one of those — which is the typed road `N1-R6` requires and
 * {@see \Modules\Connection\Api\WhatTheCodeSaysSoFar} implements. The refusal
 * has to say so, or the alternative exists and is never offered.
 */
enum WhyNothingWasScanned: string
{
    /**
     * The operator closed the scanner.
     *
     * Not a failure, and the state this is in most often: somebody opened the
     * camera, thought better of it, and pressed back. Nothing is owed except
     * the screen they came from.
     */
    case TheOperatorClosedIt = 'the_operator_closed_it';

    /**
     * The platform would not let this app have the camera (`N4-R3`).
     *
     * Answered by offering the typed road, and by saying where the decision is
     * reversed — which is the platform's settings and not this app, because
     * `N4-R4` means asking again is not something the app may do.
     */
    case TheCameraIsNotPermitted = 'the_camera_is_not_permitted';

    /**
     * There is no camera on this device at all.
     *
     * Told apart from the refusal because no amount of visiting Settings
     * changes it, and sending somebody there is the advice that wastes the most
     * of their time.
     */
    case ThereIsNoCamera = 'there_is_no_camera';

    /**
     * Whether the operator could grant this by changing a platform setting.
     *
     * The one thing a screen needs to decide between "open Settings" and
     * "there is nothing to turn on here", and it is answered once rather than
     * at each call site. The closed scanner answers false for a third reason
     * again — nothing was refused, so there is nothing to un-refuse.
     */
    public function settingsWouldHelp(): bool
    {
        return $this === self::TheCameraIsNotPermitted;
    }

    /**
     * What is said about it, as a key the template resolves.
     *
     * A key rather than a sentence, because `L1` puts the words in the
     * catalogue and `A4` keeps the translator out of a class that did not ask
     * for one. On the enum rather than on a screen for the reason `D4` gives
     * about closed sets: three reasons, three sentences, and a `match` with no
     * default arm makes a fourth reason a failure here rather than a screen
     * with nothing on it.
     *
     * `L7` is what proves each of these is a line the catalogue holds. A key
     * spelled as a literal at a call site is one nothing checks.
     */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::TheOperatorClosedIt => 'connection.the_scanner_was_closed',
            self::TheCameraIsNotPermitted => 'connection.the_camera_is_not_permitted',
            self::ThereIsNoCamera => 'connection.there_is_no_camera',
        };
    }
}
