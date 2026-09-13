<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Kernel\Api\WhyNothingWasScanned;

/**
 * The platform's own word for why a scan came back with nothing.
 *
 * `ScannerCancelled` carries a `?string $reason`, and this is the closed set of
 * what that string can be — a type rather than literals in a `match`, for the
 * reason {@see WhatTheDeviceSaid} gives about the same shape: a misspelling of
 * `'permission_denied'` is a silent fall to the default arm, and the default
 * arm here reports a refused camera as somebody who pressed back. That screen
 * offers to try again forever and never mentions the typed road, which is
 * `N4-R3` broken by a typo.
 *
 * **Three platform words, three of ours, and they do not map one to one on
 * purpose.** {@see WhyNothingWasScanned} is the vocabulary this app reasons in
 * and was chosen for the questions a screen asks; this one is whatever the
 * scanner happens to say. Keeping them apart is what lets the plugin grow a
 * fourth word without every screen in the application growing a branch.
 */
enum WhatTheScannerSaid: string
{
    /** The operator dismissed the scanner. */
    case Cancelled = 'cancelled';

    /** The platform refused this app the camera. */
    case PermissionDenied = 'permission_denied';

    /** There is no camera to open. */
    case Unavailable = 'unavailable';

    /**
     * What the scanner said, or that it said nothing this type recognises.
     *
     * `null` arrives from a scanner that was simply dismissed — the plugin
     * leaves the reason unset for the ordinary case — and an unrecognised word
     * arrives from a plugin that grew a case since this was written.
     *
     * **Both are read as a dismissal, and that is the conservative direction
     * here rather than the cautious-sounding one.** Reporting an unknown reason
     * as a refused camera would send an operator to a Settings screen with
     * nothing on it to change, and would do it on the most common path. A
     * dismissal offers to try again, which is right for a dismissal and
     * harmless for anything else: the second attempt raises the real refusal.
     */
    public static function orSimplyDismissed(?string $said): self
    {
        return self::tryFrom($said ?? '') ?? self::Cancelled;
    }

    /** What this means in the terms the rest of the application reasons in. */
    public function means(): WhyNothingWasScanned
    {
        return match ($this) {
            self::Cancelled => WhyNothingWasScanned::TheOperatorClosedIt,
            self::PermissionDenied => WhyNothingWasScanned::TheCameraIsNotPermitted,
            self::Unavailable => WhyNothingWasScanned::ThereIsNoCamera,
        };
    }
}
