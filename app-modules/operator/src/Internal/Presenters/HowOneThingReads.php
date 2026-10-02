<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function in_array;

use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;
use Modules\Operator\Internal\ViewModels\WhatOneThingIs;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;

/**
 * What one name out of a listing comes to, as the fields a screen reads.
 *
 * `F2`: data in, view model out. The listing is handed in, so a test states
 * what a machine is running and reads a screen rather than standing a stack up
 * behind a port first.
 *
 * **A service wins over a form where both could match.** That is a stack which
 * named a service after its form, and the narrower reading is the safer one:
 * agreeing about one service and being sent a whole form is the mistake that
 * costs a household something. Decided here rather than at each caller, so the
 * screen and the agreement it builds cannot come to different answers about
 * which of the two a name is.
 */
final readonly class HowOneThingReads
{
    /**
     * What the listing says about one name in it, where a service goes by it.
     *
     * Asked first, so a service wins over a form of the same name.
     */
    public function service(WhatThisStackRunsTurnedOutToBe $listing, string $named): ?WhatOneThingIs
    {
        $service = $this->row($listing, $named);

        if (! $service instanceof WhatOneServiceSays) {
            return null;
        }

        // A host-managed service takes no verb at all, and the row already
        // knows it — the verbs are about what this stack runs, and a verb
        // about something it does not would be refused by the machine.
        return new WhatOneThingIs($named, verbs: $service->isOurs ? $service->verbs : [], service: $service);
    }

    /**
     * What one name no service goes by comes to, against the forms the stack declares.
     *
     * Forms not yet read, or that could not be, leave the thing waiting: the
     * screen draws it once it knows, rather than calling a form nothing.
     */
    public function form(string $named, ?TheFormsAsFound $forms): WhatOneThingIs
    {
        if (! $forms instanceof TheFormsAsFound || ! $forms->went->cameBack()) {
            return new WhatOneThingIs($named, waits: true);
        }

        return in_array($named, $forms->names, strict: true)
            // A form has no state of its own to ask, so it takes all three:
            // what a form is, is several services at once, and the stack is the
            // thing that knows which of them a verb will reach.
            ? new WhatOneThingIs($named, isAForm: true, verbs: WhatToDoWithIt::cases())
            : new WhatOneThingIs($named);
    }

    /**
     * The row of that name in the listing, or nothing where there is none.
     *
     * The blank is refused before a {@see ServiceId} is built, because
     * `ServiceId::called()` raises on one — rightly, a service named as nothing
     * is the whole machine talking at once — and a route can say anything. No
     * form is named nothing either, so a blank comes away as *no such thing*,
     * the same as any other name this listing never carried.
     */
    private function row(WhatThisStackRunsTurnedOutToBe $listing, string $named): ?WhatOneServiceSays
    {
        if ($named === '') {
            return null;
        }

        $service = ServiceId::called($named);

        foreach ($listing->services as $row) {
            if ($row->is($service)) {
                return $row;
            }
        }

        return null;
    }
}
