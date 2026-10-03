<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;

/**
 * What tapping a stack on the list comes to, carried out of an `either()` arm.
 *
 * Three places, because the list is the way into everything and a device can be in
 * three states about one machine: nobody is signed into it, the operator is, or a
 * member is. {@see \Modules\Kernel\Api\Resumed::whoseItIs()} answers the first
 * against the other two and {@see Whose::either()} splits those,
 * and both answer with an object — an enum case is one, so the arms may build these.
 *
 * **Not the surface a person is handed at sign-in, though two of the three agree
 * with it.** That is about which of this product's two applications a person is
 * handed, which is a fact about *them*; this is about where one control goes, which
 * is a fact about a *device* and has a third answer that is no application at all.
 *
 * Published because the list of stacks reads it on both surfaces, and the menu
 * reads the same answer to decide whose it is.
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

    /**
     * Where opening this stack leads, from whose session this phone holds for it.
     *
     * The subject without the session: a place that opens a stack has no use
     * for a credential, so it asks a question whose answer does not contain
     * one.
     */
    public static function for(SecureStorage $storage, StackId $stack): self
    {
        return $storage->resume($stack)->whoseItIs(
            nobody: static fn(): self => self::TheSignIn,
            theirs: static fn(Whose $whose): self => $whose->either(
                operator: static fn(): self => self::TheReport,
                member: static fn(): self => self::WhatTheyAreOwed,
            ),
        );
    }
}
