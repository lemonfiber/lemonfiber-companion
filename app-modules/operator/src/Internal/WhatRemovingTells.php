<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** What removing a stack from this phone tells the stack first. */
enum WhatRemovingTells
{
    /** Nothing: the stack holds nothing of this phone's waking. */
    case Nothing;

    /** To forget this phone's endpoint. */
    case ToForgetThisPhone;
}
