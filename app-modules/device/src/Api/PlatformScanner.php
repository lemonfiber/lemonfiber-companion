<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Closure;
use Lemonfiber\Native\Scanning as TheCamera;
use Lemonfiber\Native\WhyNothingWasRead;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Permission;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyNothingWasScanned;

/**
 * This application's own scanner, reading one pairing code.
 *
 * Thin, like every adapter here. What it decides is how the bridge's answer
 * becomes one {@see WhatTheCameraSaw}, and the interesting half of that is the
 * refusal: a camera declined in the dialog a moment ago and one settled in
 * settings some time ago come back under the same word, and the second fact the
 * bridge carries is what tells them apart. They need opposite sentences — try
 * again, or go to Settings — and a screen has nothing else to choose on.
 *
 * **The prompt is this application's own sentence, asked for by the case rather
 * than by a key written here.** The platform paints it over the camera preview,
 * and on the first scan the same call is what raises the permission dialog — so
 * it is the last thing the operator reads before the system takes over. A key
 * spelled as a literal is one nothing checks: `PermissionsAreExplainedTest`
 * proves every permission has that line in every locale, and it can only prove
 * it about the string {@see Permission::reason()} builds.
 *
 * **The code comes back in the answer rather than on an event.** That is the
 * bridge's doing rather than this class's, and it is the reason this adapter is
 * eight lines instead of a pair of callbacks: there is one call, it blocks while
 * the camera is on screen, and what it answers is what was read.
 */
final readonly class PlatformScanner implements Scanning
{
    public function __construct(private TheCamera $camera, private Words $words) {}

    public function forAPairingCode(Closure $saw): void
    {
        $read = $this->camera->forAPairingCode($this->words->for(Permission::Camera->reason()));

        $saw($read->either(
            read: static fn(string $payload): WhatTheCameraSaw => WhatTheCameraSaw::read($payload),
            nothing: static fn(WhyNothingWasRead $why, bool $again): WhatTheCameraSaw
                => WhatTheCameraSaw::nothing(self::meaning($why, $again)),
        ));
    }

    /**
     * What one of the bridge's words means in the terms the app reasons in.
     *
     * A `match` with no default arm, so a word added to the bridge fails here by
     * name rather than falling silently into whichever case was written last —
     * and the case written last here would offer another go for ever without
     * ever mentioning the typed road.
     *
     * The refused camera splits on the second fact rather than on a fourth
     * word, because the platform gives one refusal and two situations: what
     * differs is not what happened but what can still be done about it.
     */
    private static function meaning(WhyNothingWasRead $why, bool $mayAskAgain): WhyNothingWasScanned
    {
        return match ($why) {
            WhyNothingWasRead::TheOperatorClosedIt => WhyNothingWasScanned::TheOperatorClosedIt,
            WhyNothingWasRead::ThereIsNoCamera => WhyNothingWasScanned::ThereIsNoCamera,
            WhyNothingWasRead::TheCameraIsNotPermitted => $mayAskAgain
                ? WhyNothingWasScanned::TheCameraWasDeclined
                : WhyNothingWasScanned::TheCameraIsNotPermitted,
        };
    }
}
