<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealStanding;

/**
 * What the phone kept, cleared on opening where it can no longer be read.
 *
 * **Asked before anything kept is sealed or opened**, which is what the seal
 * asks of whoever keeps something: its standing is the one answer that says a
 * key was made, and it says it once. So the opening asks it first, and a key
 * made afresh — a restore onto another phone, a keychain reset — clears every
 * store the phone keeps: what was sealed under the old key cannot be opened
 * under the new one.
 *
 * **Pairings and sessions stay.** They live in the platform's secure storage,
 * outside every store this clears, and the operator is told only that saved
 * data was cleared.
 *
 * Where the key cannot be had at all nothing is cleared: a store that would not
 * open may open next time, and what was sealed under the key it holds is still
 * readable then.
 */
final readonly class ClearingWhatCannotBeRead
{
    public function __construct(private Sealed $seal, private ForgetsEverythingKept $kept) {}

    /** Clear what the phone kept where its key was made just now, and say whether that happened. */
    public function onOpening(): WhatWasKeptAtOpening
    {
        return match ($this->seal->standing()) {
            SealStanding::MadeAfresh => $this->cleared(),
            SealStanding::Held, SealStanding::Unavailable => WhatWasKeptAtOpening::AsItWas,
        };
    }

    /**
     * Clear every store, and say so only where there was something to clear.
     *
     * A key is made afresh on the first launch too, when nothing has been kept
     * yet, and telling somebody who has just installed the app that its saved
     * data was cleared would be a sentence about something that never was.
     */
    private function cleared(): WhatWasKeptAtOpening
    {
        return $this->kept->forgetEverything()->howMany() > 0 ? WhatWasKeptAtOpening::Cleared : WhatWasKeptAtOpening::AsItWas;
    }
}
