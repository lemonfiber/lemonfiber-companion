<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of the three things a service held back waits for, in the stack's word for it.
 */
enum KindOfThingWaitedFor: string
{
    /** A setting only the operator holds, or one holding a value outside its choices. */
    case Setting = 'setting';

    /** A secret nobody has entered. */
    case Secret = 'secret';

    /** The service it runs through, held back itself. */
    case Service = 'service';
}
