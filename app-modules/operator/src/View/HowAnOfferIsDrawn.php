<?php

declare(strict_types=1);

namespace Modules\Operator\View;

/**
 * How a button for something the stack is asked to do is drawn while it can be pressed.
 *
 * Three looks a screen already draws its controls in. Once it cannot be
 * pressed every look is the platform's disabled button, because a line of
 * words cannot be marked as not usable and a screen reader announces a
 * disabled button as such.
 */
enum HowAnOfferIsDrawn: string
{
    /** The filled or tonal bar. */
    case Button = 'button';

    /** Text in the colour of the operator's own actions. */
    case Quiet = 'quiet';

    /** A row that leads on, with its chevron. */
    case Link = 'link';
}
