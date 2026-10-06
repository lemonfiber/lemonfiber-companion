<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a stack offers one action, as a screen draws its button.
 *
 * Five answers, and each is a different sentence beside the button. Four come
 * from what the stack declares it can do, and the fifth is not knowing: a
 * stack that could not be asked keeps its actions offered, and tapping one
 * reports that it could not be reached rather than the button being taken
 * away before anybody tried.
 *
 * **Not {@see Availability}, which is what the stack said about a capability
 * it has.** This is what the app makes of it for one button, absence and not
 * knowing included: an `Availability` cannot say *too old* or *could not ask*,
 * because the stack never says either.
 */
enum WhetherItIsOffered: string
{
    /** The stack has it and this account may ask for it. */
    case Offered = 'offered';

    /** The stack has it, and something on the machine has to be set up before it does anything. */
    case NotSetUp = 'not_set_up';

    /** The stack has it, and it is not this account's to ask for. */
    case NotTheirs = 'not_theirs';

    /** The stack does not have it: the lemonfiber on it is older than this. */
    case NeedsANewerLemonfiber = 'needs_a_newer_lemonfiber';

    /** The stack could not be asked what it offers, so it is offered and the asking reports why. */
    case NotKnown = 'not_known';

    /**
     * Whether its button can be pressed.
     *
     * A capability that is present and not set up is offered, and says what
     * is missing; one the account may not use and one the stack does not have
     * are shown and explained rather than hidden, and neither is a button that
     * would work. Not knowing offers it, because taking a button away from a
     * stack that did not answer is deciding for the operator what an answer
     * would have said.
     */
    public function offersAnAction(): bool
    {
        return match ($this) {
            self::Offered, self::NotSetUp, self::NotKnown => true,
            self::NotTheirs, self::NeedsANewerLemonfiber => false,
        };
    }

    /**
     * Whether what would provide it is a newer lemonfiber, which the stack's updates screen offers.
     *
     * The one answer whose remedy is a place in this app rather than a
     * sentence: the operator updates the machine from its updates screen.
     */
    public function isProvidedByAnUpdate(): bool
    {
        return $this === self::NeedsANewerLemonfiber;
    }
}
