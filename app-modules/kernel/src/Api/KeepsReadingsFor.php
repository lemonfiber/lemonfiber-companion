<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How long readings are kept, as a setting of the phone.
 *
 * Kept with the phone's other settings, and asked by what lets go of readings
 * older than it, so each is tested over a stand-in for the other. Until the
 * operator chooses, it is {@see HowLongReadingsAreKept::standard()}.
 */
interface KeepsReadingsFor
{
    /** How long readings are kept, as the operator chose, or the standard. */
    public function keptFor(): HowLongReadingsAreKept;

    /** Keep the operator's choice, and answer what is in force. */
    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept;
}
