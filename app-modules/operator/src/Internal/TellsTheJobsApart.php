<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use UnitEnum;

/** The jobs a screen follows, told apart from any other a hook may be asked about. */
trait TellsTheJobsApart
{
    /** The job asked after, where it is one of these; any other raises {@see NotAJobItFollows}. */
    public static function asked(UnitEnum $for): static
    {
        return $for instanceof static ? $for : throw NotAJobItFollows::named($for);
    }
}
