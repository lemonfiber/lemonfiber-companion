<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** The bare words the `notify` envelope answers with where an arm carries nothing more. */
enum WhatTheNotifyWordsAre: string
{
    /** The operator chose a back-end. */
    case Operator = 'operator';

    /** A back-end has never delivered. */
    case Unproven = 'unproven';

    /** The back-end confirmed a delivery. */
    case Confirmed = 'confirmed';

    /** A delivery was handed over with no confirmation. */
    case Sent = 'sent';
}
