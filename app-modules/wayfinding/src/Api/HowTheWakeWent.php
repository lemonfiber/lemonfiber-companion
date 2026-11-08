<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

/** What one wake while the app is away came to. */
enum HowTheWakeWent
{
    /** Notifications were off, or the operator's choice could not be read, so no stack was reached. */
    case NothingToTell;

    /** The app was in front of somebody and hearing its stacks itself, so no stack was reached. */
    case TheAppIsOpen;

    /** A push woke the app for a stack that does not wake this phone, or that it is not signed in to as the operator, so it was dropped. */
    case NotWokenFor;

    /** Every stack the operator is signed in to said what it raised meanwhile, ended, or could not be heard. */
    case HeardEveryStack;

    /** The wake read as long as it may, and some stack had still not said its first summary. */
    case GaveUpOnSome;
}
