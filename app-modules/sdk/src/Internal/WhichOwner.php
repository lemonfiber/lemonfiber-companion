<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The words the `settings` envelope says whose a setting or a secret is with.
 */
enum WhichOwner: string
{
    case Core = 'core';

    case Namespace = 'namespace';
}
