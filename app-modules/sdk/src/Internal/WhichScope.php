<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The word inside a copy's `scope` that says which of the three it is.
 *
 * The wire's words, so a scope this app has no case for is refused by name
 * rather than read as whichever case came first.
 */
enum WhichScope: string
{
    /** Every service, lemonfiber's own configuration, and the stack. */
    case WholeStack = 'whole_stack';

    /** One named service. */
    case Service = 'service';

    /** A setup lemonfiber does not manage. */
    case Existing = 'existing';
}
