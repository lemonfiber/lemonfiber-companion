<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * Which of the two things the update endpoint is asked about, as the `what` parameter spells it.
 *
 * A value a request sends rather than a field an envelope carries, so it sits
 * apart from the field vocabularies under `Fields`: those hold each word the
 * wire reads once, and a support bundle's `taken.stack` is a field spelled the
 * same as this value.
 */
enum WhichUpdate: string
{
    /** The services, as opposed to this copy of lemonfiber. */
    case TheStack = 'stack';
}
