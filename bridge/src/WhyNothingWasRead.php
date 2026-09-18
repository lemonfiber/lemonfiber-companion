<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the camera came back without a pairing code.
 *
 * A closed set on both sides of the wire, so a wrong word is caught here rather
 * than falling through a `default` arm into whichever case happened to be
 * written last. The default arm is what makes this worth a type: a misspelt
 * refusal reads as *the operator pressed back*, and that screen offers another
 * go for ever without ever mentioning that the code can be typed instead.
 *
 * The same three words `CameraRule.kt` and `CameraRule.swift` answer with, and
 * three rather than one because each has a different thing for the operator to
 * do about it: come back when they are ready, go to Settings, or stop looking
 * for a camera this device does not have.
 */
enum WhyNothingWasRead: string
{
    /** They backed out of the scanner. Not a failure, and the ordinary way out. */
    case TheOperatorClosedIt = 'the_operator_closed_it';

    /** The platform would not let this application have the camera. */
    case TheCameraIsNotPermitted = 'the_camera_is_not_permitted';

    /** There is no camera on this device at all. */
    case ThereIsNoCamera = 'there_is_no_camera';

    /**
     * What the bridge said, or that it said nothing this type recognises.
     *
     * A bridge with no device behind it answers nothing at all, which is every
     * machine that is not a handset. Both that and an unrecognised word are
     * read as {@see self::TheOperatorClosedIt}, and that is the conservative
     * direction here rather than the cautious-sounding one: reporting an
     * unknown reason as a refused camera would send an operator to a Settings
     * screen with nothing on it to change, and would do it on the most common
     * path. A dismissal offers another go, which is right for a dismissal and
     * harmless for anything else — the second attempt raises the real refusal.
     */
    public static function orSimplyDismissed(?string $said): self
    {
        return self::tryFrom($said ?? '') ?? self::TheOperatorClosedIt;
    }
}
