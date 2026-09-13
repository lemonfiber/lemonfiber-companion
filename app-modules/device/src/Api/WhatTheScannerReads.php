<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Kernel\Api\WhatCarriesPairingMaterial;

/**
 * The plugin's own word for a format its scanner can read.
 *
 * `nativephp/mobile` takes an array of format names — `qr`, `ean13`, `code128`
 * and five more — and this is the half of that list this application has an
 * opinion about. {@see WhatCarriesPairingMaterial} is the opinion; this is the
 * spelling, and they are separate for the reason {@see WhatTheScannerSaid} and
 * {@see WhatTheDeviceSaid} are separate from what they translate into: a fact
 * about lemonfiber and a fact about somebody's package are not the same fact,
 * and a package that renames a format should not reach the kernel.
 *
 * **A backed enum, because the value is what goes on the wire.** The plugin
 * compares the string, and an unrecognised one is dropped without complaint —
 * the scanner opens and reads nothing, which looks exactly like a camera
 * pointed at the wrong thing.
 */
enum WhatTheScannerReads: string
{
    /** A QR code. */
    case Qr = 'qr';

    /**
     * How this plugin spells the carrier the material comes in.
     *
     * A `match` with no default arm, so a second carrier added to the kernel
     * fails here by name rather than falling silently into whichever case was
     * written last — which would mean a scanner reading for the wrong format
     * and reporting the stack's own code as unreadable.
     */
    public static function forMaterialCarriedBy(WhatCarriesPairingMaterial $carrier): self
    {
        return match ($carrier) {
            WhatCarriesPairingMaterial::QrCode => self::Qr,
        };
    }
}
