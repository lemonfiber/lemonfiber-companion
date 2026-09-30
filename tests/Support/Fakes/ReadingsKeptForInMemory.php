<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\KeepsReadingsFor;

/**
 * How long readings are kept, held in memory, and held by
 * `KeepsReadingsForContractTest` to what the phone's settings promise: the
 * standard until a choice is kept, and the choice after.
 */
final class ReadingsKeptForInMemory implements KeepsReadingsFor
{
    private HowLongReadingsAreKept $kept;

    private function __construct(private readonly bool $keeps)
    {
        $this->kept = HowLongReadingsAreKept::standard();
    }

    /** A phone that keeps the choice. */
    public static function standard(): self
    {
        return new self(keeps: true);
    }

    /** A phone that can keep nothing: a choice is in force as it is made, and not kept. */
    public static function keepingNothing(): self
    {
        return new self(keeps: false);
    }

    public function keptFor(): HowLongReadingsAreKept
    {
        return $this->kept;
    }

    public function keepFor(HowLongReadingsAreKept $kept): HowLongReadingsAreKept
    {
        if ($this->keeps) {
            $this->kept = $kept;
        }

        return $kept;
    }
}
