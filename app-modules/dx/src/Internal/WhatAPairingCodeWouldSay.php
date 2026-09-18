<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Modules\Kernel\Api\WhatPairingMaterialSays;

use function str_repeat;

/**
 * Pairing material for a machine nobody has met, written the way a stack writes it.
 *
 * The first-run sequence ends at pairing, and with stand-ins on the sequence
 * could be walked and not finished: {@see \Modules\Dx\Api\ADeviceAlreadyPaired}
 * seeds three machines, so the first run is reachable only with that affordance
 * off — and with it off there is nothing to pair with either. The one screen
 * this module could not reach was the one an operator meets first.
 *
 * **The three keys come from the enum that defines them.** `Pairing::read()`
 * refuses a key `WhatPairingMaterialSays` does not name, so spelling them here
 * would be a second copy of the format — and the first thing a second copy does
 * is survive a rename of the first. Everything else about the material is this
 * module's own: the address resolves nowhere and the digest is one no
 * certificate has.
 */
final readonly class WhatAPairingCodeWouldSay
{
    /**
     * Reserved by RFC 2606, so it resolves nowhere.
     *
     * Pairing material that could reach a real stack is refused. An
     * address that cannot be resolved is the strongest form of that: were a
     * request ever to escape the stand-in, the failure would be a name that
     * does not exist rather than a connection to somebody's actual machine.
     */
    private const string AT = 'https://a-machine-you-have-not-met.invalid:8443';

    /** How many bytes a SHA-256 digest is, doubled by its hex spelling. */
    private const int A_SHA256 = 32;

    /**
     * Far enough ahead that a stand-in is never material that has expired.
     *
     * `Pairing::read()` reads staleness before shape, which is right — a code
     * too old should not be reported as a code that is malformed — and a
     * stand-in that went stale would take the first run with it, at a moment
     * nobody would connect to a date written here.
     *
     * Written rather than counted from a clock. `B1` keeps time behind a port,
     * and material assembled from the moment it was read would make two runs of
     * the same stand-in answer differently.
     */
    private const int LONG_AFTER_ANY_RUN = 4_070_908_800;

    /**
     * The code, as the characters a camera would have seen.
     *
     * @throws JsonException where the three keys cannot be written as JSON,
     *                       which is a state this cannot reach and the analyser
     *                       cannot know that
     */
    public static function asItWouldBeScanned(): string
    {
        return json_encode([
            WhatPairingMaterialSays::Address->value => self::AT,
            WhatPairingMaterialSays::Fingerprint->value => str_repeat('cd', self::A_SHA256),
            WhatPairingMaterialSays::Expires->value => self::LONG_AFTER_ANY_RUN,
        ], JSON_THROW_ON_ERROR);
    }

}
