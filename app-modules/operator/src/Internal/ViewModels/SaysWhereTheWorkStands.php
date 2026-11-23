<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A screen's report on work it sent, which says where that work stands. */
interface SaysWhereTheWorkStands
{
    public WhereTheWorkStands $stands { get; }
}
