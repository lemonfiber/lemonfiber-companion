<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack says about a capability it has.
 *
 * Three cases, and the fourth answer is deliberately not one of them.
 * A capability the stack does not have has to be **absent**
 * rather than reported as false — so absence is the collection not holding it,
 * not a case here. A `NotSupported` case would make "this stack cannot do it"
 * and "this stack can do it but you may not" two neighbours in one list, and a
 * screen would end up treating them the same because they are the same shape.
 *
 * The distinction is not pedantry: each must be reported as
 * itself. An operator told "this stack cannot back up" when the truth is "your
 * credential may not" goes looking for a feature that is right there, and one
 * told "unavailable" when the truth is "not configured yet" never finds the
 * setup that would turn it on.
 */
enum Availability: string
{
    /** Present, configured, and this credential may use it. */
    case Available = 'available';

    /** Present, and nothing has been set up for it yet. */
    case Unconfigured = 'unconfigured';

    /** Present and configured, and this credential may not use it. The stack's own word for it. */
    case NotPermitted = 'unpermitted';

    /**
     * What a button for it says.
     *
     * Each is reported as itself: one not set up is offered and says what is
     * missing, and one this credential may not use says it is not theirs
     * rather than that the stack cannot.
     */
    public function offered(): WhetherItIsOffered
    {
        return match ($this) {
            self::Available => WhetherItIsOffered::Offered,
            self::Unconfigured => WhetherItIsOffered::NotSetUp,
            self::NotPermitted => WhetherItIsOffered::NotTheirs,
        };
    }
}
