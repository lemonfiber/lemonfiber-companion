<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** The words the forms read's query takes, which are asked and never read back. */
enum WhatTheFormsReadIsAsked: string
{
    /** The form a rehearsal is asked about. */
    case Form = 'form';
}
