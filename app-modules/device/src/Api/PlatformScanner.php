<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Closure;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Permission;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatCarriesPairingMaterial;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Events\Scanner\ScannerCancelled;
use Native\Mobile\PendingScanner;

/**
 * The platform's own scanner, reading one pairing code.
 *
 * Thin, like every adapter here. What it decides is how two NativePHP events
 * become one {@see WhatTheCameraSaw}, and the interesting half of that is the
 * refusal: `ScannerCancelled` is dispatched both when somebody presses back and
 * when the camera permission is denied, so the reason string is what tells a
 * screen offering the typed road (`N4-R3`) from one offering another go.
 * {@see WhatTheScannerSaid} is where that word is read.
 *
 * **One format, and which one is not this class's to decide.** The plugin
 * scans eight; {@see WhatCarriesPairingMaterial} says what lemonfiber's material
 * travels in and {@see WhatTheScannerReads} says how this package spells it.
 * That was a `['qr']` here, which made a fact about pairing material look like
 * a setting on a scanner.
 *
 * **The prompt is `N4-R2`'s own sentence, asked for by the case rather than by
 * a key written here.** The platform paints it over the camera preview, and on
 * the first scan this call is also what raises the permission dialog — so it is
 * the last thing the operator reads before the system takes over. A key spelled
 * as a literal is one nothing checks: `PermissionsAreExplainedTest` proves every
 * permission has that line in every locale, and it can only prove it about the
 * string {@see Permission::reason()} builds. A scanner captioned in English on a
 * Dutch device, or captioned with a key, is what `L1` exists to prevent.
 *
 * **Both callbacks are registered before the scan starts.** `PendingScanner`
 * runs `scan()` from `__destruct()` where it was not called explicitly, so a
 * builder that goes out of scope mid-chain opens the camera with only the
 * handlers registered so far — the same destructor-shaped trap
 * {@see PlatformNotifier} documents from the other side. Built and started in
 * one statement for that reason.
 */
final readonly class PlatformScanner implements Scanning
{
    /**
     * @param Closure(): PendingScanner $open how a scanner is started
     *
     * @param-later-invoked-callable $open Called once per attempt, from
     * {@see forAPairingCode()} below.
     *
     * **A closure rather than the `Scanner` itself, and the reason is its
     * shape.** `Scanner::scan()` is static, so holding one would mean calling a
     * static method through an instance — which the analyser refuses for this
     * repository's own code and which reads as an instance method to everybody
     * afterwards. Taking the act instead of the object also means a test can
     * hand over a scanner that does not open a camera, which is otherwise
     * impossible: there is no seam in a static call.
     */
    public function __construct(private Closure $open, private Words $words) {}

    public function forAPairingCode(Closure $saw): void
    {
        ($this->open)()
            ->formats([WhatTheScannerReads::forMaterialCarriedBy(WhatCarriesPairingMaterial::QrCode)->value])
            ->prompt($this->words->for(Permission::Camera->reason()))
            ->codeScanned(static function (CodeScanned $found) use ($saw): void {
                $saw(WhatTheCameraSaw::read($found->data));
            })
            ->scannerCancelled(static function (ScannerCancelled $stopped) use ($saw): void {
                $saw(WhatTheCameraSaw::nothing(
                    WhatTheScannerSaid::orSimplyDismissed($stopped->reason)->means(),
                ));
            })
            ->scan();
    }
}
