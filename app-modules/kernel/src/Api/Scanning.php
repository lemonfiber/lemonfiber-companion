<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The camera, asked to read a pairing code.
 *
 * `N1-R6`'s first road. A port because there is no camera on a laptop and
 * because the alternative — a screen reaching for the platform's scanner
 * directly — is the one shape a suite cannot drive: `A3` refuses it, and the
 * practical cost is that the whole of the scanned road would be untestable.
 *
 * **It is one method and it hands the answer back, rather than returning it.**
 * The platform's scanner is a screen of its own: it takes the display, and the
 * result arrives later through NativePHP's callback machinery, after this call
 * has returned. A port shaped `read(): WhatTheCameraSaw` would be a promise
 * only a fake could keep, and the adapter would have to block the runloop to
 * keep it — which on a handset is the app hanging.
 *
 * **The permission is not separately askable, and that is the platform's
 * doing.** `nativephp/mobile` exposes no camera-permission check: there is no
 * `checkPermission()` to read and no way to raise the prompt on its own. What
 * there is is the scan, which prompts on first use — `N4-R1` by construction —
 * and reports a refusal as a cancellation. So `N4-R4`'s "do not ask again" is
 * the platform's to keep here rather than this app's, and what the app owes is
 * `N4-R3`: the refusal arrives as {@see WhyNothingWasScanned::TheCameraIsNotPermitted}
 * and the typed road is offered.
 *
 * That is a gap worth naming rather than papering over, because it is the one
 * place this application cannot enforce a requirement it is held to. It is
 * written here, where somebody adding a `Permissions` port will read it, rather
 * than discovered by writing one and finding nothing to implement it with.
 *
 * **`N4-R2` is the caller's.** The app's own sentence goes up before this is
 * called, because this call is what raises the platform's prompt. No signature
 * can hold that, which is why `PermissionsAreExplainedTest` asks the catalogue
 * whether the sentence exists at all.
 */
interface Scanning
{
    /**
     * Open the camera, and hand back what it saw.
     *
     * Called once per attempt rather than left listening. A scanner left open
     * is a camera left on, and the platform's own screen is modal anyway — the
     * operator is either in it or they are not.
     *
     * @param Closure(WhatTheCameraSaw): void $saw
     *
     * @param-later-invoked-callable $saw It is called after this method has
     * returned, from the runloop, which is what the annotation records: a
     * reader tracing this will not find the call below it.
     */
    public function forAPairingCode(Closure $saw): void;
}
