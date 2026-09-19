<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What tapping a stack on the list comes to, carried out of an `either()` arm.
 *
 * Three places, because the list is the way into everything and a device can be in
 * three states about one machine: nobody is signed into it, the operator is, or a
 * member is. {@see \Modules\Kernel\Api\Resumed::whoseItIs()} answers the first
 * against the other two and {@see \Modules\Kernel\Api\Whose::either()} splits those,
 * and both answer with an object — an enum case is one, so the arms may build these.
 *
 * **Not {@see WhichSurfaceTheyAreGiven}, though two of the three agree with it.**
 * That one is about which of this product's two applications a person is handed,
 * which is a fact about *them*; this is about where one control goes, which is a
 * fact about a *device* and has a third answer that is no application at all. Folding
 * the sign-in screen into the other enum would put a case in it that the screen which
 * uses it can never reach, and a `match` arm nothing arrives at is a line no test can
 * hold to being right.
 *
 * `Internal` because it is a detail of how this surface reads a device's state, and
 * `E2`'s promise is that anything here can be renamed without reading another module.
 */
enum WhereTappingLeads: string
{
    /** Nobody is signed into this machine from here, so the password is the first thing. */
    case TheSignIn = 'the_sign_in';

    /** The operator is, and the report is what they came to the list to reach. */
    case TheReport = 'the_report';

    /**
     * A member is, so the list hands them the reading that is theirs.
     *
     * The same answer the sign-in screen gives, arrived at a day later. A member
     * whose session outlived the app being closed is still a member, and a list that
     * remembered only *that* a session was held would hand them the operator's
     * machine report every launch after the first.
     */
    case WhatTheyAreOwed = 'what_they_are_owed';
}
