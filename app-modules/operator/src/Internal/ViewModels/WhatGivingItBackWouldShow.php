<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What giving back one category would come to, as the stack's offer reads before a yes.
 *
 * `$category` is null wherever there is nothing to agree to: the screen named
 * no category, the offer holds none of it, or the reading did not come back.
 */
final readonly class WhatGivingItBackWouldShow
{
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $namesACategory,
        public ?ACategoryAsShown $category,
    ) {}
}
