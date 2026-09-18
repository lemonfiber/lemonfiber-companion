<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the notification centre did not put something in front of anybody.
 *
 * A closed set on both sides of the wire, so a wrong word is caught here rather
 * than falling through a `default` arm into whichever case happened to be
 * written last. The three are told apart because the sentence a screen says
 * about each is different: one is a permission, one is a switch the operator
 * turned off, and one is the device saying no.
 */
enum WhyNothingWasTold: string
{
    /** The operator has not allowed notifications, or has withdrawn that. */
    case NotPermitted = 'not_permitted';

    /**
     * The channel this application posts on has been switched off.
     *
     * Android only. iOS has one switch per application, which the permission
     * already covers, so this word exists on the wire and is never the answer
     * there — the same shape as a refusal one platform can make and the other
     * cannot.
     */
    case NoSuchChannel = 'no_such_channel';

    /** The platform refused to post it, and said nothing useful about why. */
    case TheDeviceRefused = 'the_device_refused';

    /**
     * What the bridge said, or that it said nothing this type recognises.
     *
     * Read as the device having refused, which is what a machine with no
     * notification centre behind it has in fact done. The alternative — reading
     * it as a permission — would put a screen in front of somebody offering to
     * ask for something no dialog on that machine could grant.
     */
    public static function orTheDeviceRefused(?string $said): self
    {
        return self::tryFrom($said ?? '') ?? self::TheDeviceRefused;
    }
}
