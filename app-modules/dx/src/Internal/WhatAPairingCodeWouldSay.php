<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function intdiv;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Modules\Kernel\Api\Fingerprint;
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
 * **The keys come from the enum that defines them.** `Pairing::read()`
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

    /**
     * The name this machine gives itself, the same in every code it issues.
     *
     * Fixed rather than drawn, because a stack keeps one identifier across
     * every piece of material it hands out: two codes from this stand-in are
     * two codes for one machine, as two from a real stack would be.
     */
    private const string CALLING_ITSELF = '5e7a9c1b3d5f7092a4c6e8f0b2d4f6a8';

    /**
     * When the material expires: {@see LongAfterAnyRun}, so a stand-in is
     * never material that has expired.
     *
     * `Pairing::read()` reads staleness before shape, which is right — a code
     * too old should not be reported as a code that is malformed — so it is
     * the first thing a stale stand-in would be refused for.
     */
    private const int EXPIRES = LongAfterAnyRun::IN_SECONDS;

    /**
     * The code, as the characters a camera would have seen.
     *
     * @throws JsonException where the keys cannot be written as JSON,
     *                       which is a state this cannot reach and the analyser
     *                       cannot know that
     */
    public static function asItWouldBeScanned(): string
    {
        return json_encode([
            WhatPairingMaterialSays::Address->value => self::AT,
            WhatPairingMaterialSays::Fingerprint->value => str_repeat('cd', intdiv(Fingerprint::CHARACTERS, 2)),
            WhatPairingMaterialSays::Expires->value => self::EXPIRES,
            WhatPairingMaterialSays::Stack->value => self::CALLING_ITSELF,
        ], JSON_THROW_ON_ERROR);
    }

}
