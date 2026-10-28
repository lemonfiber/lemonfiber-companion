<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What carrying out an agreement came to, flattened for a template.
 *
 * The sibling of {@see WhatTheStackWouldPutRight}, and separate from it: one
 * is a listing of what a machine *would* do and the other a record of what it
 * *did*, and a type holding either would be one a template has to ask which it
 * is looking at. What the stack did is there only where it answered.
 */
final readonly class WhatThisStackPutRight
{
    /** @param ?WhatTheMendingCameTo $came what the stack did about each repair, where it answered */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?WhatTheMendingCameTo $came = null,
    ) {}
}
