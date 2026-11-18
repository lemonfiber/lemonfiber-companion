<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why work ended before it finished, as the stack says it.
 */
enum WhyTheWorkEnded: string
{
    case Released = 'released';

    case LetGo = 'let-go';

    case Quiet = 'quiet';
}
