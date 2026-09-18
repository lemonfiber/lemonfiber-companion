<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What pairing material travels in, when a camera is what reads it.
 *
 * The companion to {@see WhatPairingMaterialSays}, which names the three keys
 * the payload holds: this names the thing the payload is written into.
 * `ADR-0018` decides both — the material is a JSON object carrying an address
 * and a certificate digest, and it is shown on the stack's own screen for a
 * camera to read.
 *
 * **Here rather than in the scanner that reads it.** It was a `['qr']` in
 * {@see \Modules\Device\Api\PlatformScanner}, which made a fact about pairing
 * material look like a setting on a plugin. It is not: the reason the app scans
 * one format rather than eight is that seven of them cannot carry a JSON
 * object, and a `code39` barcode wandering into frame while somebody lines up
 * their stack's screen is not pairing material — it is a product code that
 * would dismiss the scanner and be reported as a code that would not parse.
 *
 * **A pure enum, so the platform's word is not in the kernel.**
 * `nativephp/mobile` spells this `'qr'`; another scanner would spell it
 * something else, and neither spelling is a fact about lemonfiber.
 * {@see \Modules\Device\Api\WhatTheScannerReads} is where this becomes that
 * package's word, which is the same split {@see HowItWasRead} has from
 * {@see \Modules\Device\Api\WhatTheDeviceSaid}.
 *
 * **One case, and it is still an enum.** A `const` would say the same thing
 * and would say it in whichever class happened to hold it; what this is, is a
 * closed set that currently has one member — the day a stack offers material in
 * a second carrier, the adapter's `match` has no arm for it and says so by
 * name. That is the property `D4` is written about, and it is worth more here
 * than the line it costs: the alternative failure is a scanner silently
 * ignoring a format the stack is showing.
 */
enum WhatCarriesPairingMaterial
{
    /** A QR code on the stack's own screen (`ADR-0018`). */
    case QrCode;
}
