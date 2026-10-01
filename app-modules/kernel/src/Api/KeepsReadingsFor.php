<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How long readings are kept, as a setting of the phone.
 *
 * Readings are `health`'s, and the phone's settings are kept together by the
 * module that keeps them; this is how the one asks the other. Until the
 * operator chooses, it is {@see HowLongReadingsAreKept::standard()}.
 */
interface KeepsReadingsFor
{
    /** How long readings are kept, as the operator chose, or the standard. */
    public function keptFor(): HowLongReadingsAreKept;

    /** Keep the operator's choice, and answer what is in force. */
    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept;
}
