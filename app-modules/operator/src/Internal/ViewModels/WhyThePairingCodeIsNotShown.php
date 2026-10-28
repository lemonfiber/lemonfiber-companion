<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Why a pairing code the stack answered about is not shown. */
enum WhyThePairingCodeIsNotShown
{
    /** The code has stopped being good. */
    case Expired;

    /** The stack's check code is not the one a phone typing the line works out. */
    case CheckedDifferently;
}
