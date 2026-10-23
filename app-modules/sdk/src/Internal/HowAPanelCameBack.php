<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * Whether a dashboard panel's source answered, as the panel says it in its `panel` word.
 */
enum HowAPanelCameBack: string
{
    /** The source answered, and the panel's `data` is its content. */
    case Ready = 'ready';

    /** The source could not be reached, and the panel's `data` says why. */
    case Unavailable = 'unavailable';
}
