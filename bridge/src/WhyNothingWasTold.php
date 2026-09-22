<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the notification centre did not put something in front of anybody.
 *
 * A closed set on both sides of the wire, so a wrong word is caught here rather
 * than falling through a `default` arm into whichever case happened to be
 * written last. They are told apart because the sentence a screen says about
 * each is different: a permission, a switch the operator turned off, a moment
 * that has been, a repeat no calendar has, and the device simply saying no.
 *
 * **Every word both native halves can answer with, rather than only the ones
 * `Show` can.** Two of these reach a caller from `Schedule` and
 * `ScheduleRecurring` alone, and leaving them out is what a handset found: an
 * unrecognised word falls to {@see self::TheDeviceRefused}, so a repeat this
 * bridge deliberately refused came back as the device having failed.
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

    /**
     * The moment a notification was wanted at has already been.
     *
     * Scheduling only. Shown immediately instead would be the tempting
     * reading and the wrong one: an alert about something that was going to
     * happen is not an alert about something that has.
     */
    case TheTimeHasPassed = 'the_time_has_passed';

    /**
     * The repeat names something no calendar has.
     *
     * Scheduling only. An hour of twenty-five, a month of thirteen, a day of
     * the month past the twenty-eighth — the last because the two platforms'
     * calendars disagree about what to do with a thirty-first, and a repeat an
     * operator cannot predict is worse than one that was declined.
     */
    case NoSuchRepeat = 'no_such_repeat';

    /** The platform refused to post it, and said nothing useful about why. */
    case TheDeviceRefused = 'the_device_refused';

    /**
     * What the bridge said, or that it said nothing this type recognises.
     *
     * Read as the device having refused, which is what a machine with no
     * notification centre behind it has in fact done. The alternative — reading
     * it as a permission — would put a screen in front of somebody offering to
     * ask for something no dialog on that machine could grant.
     *
     * **Two branches rather than `tryFrom($said ?? '')`.** Routing *nothing
     * said* through the empty string makes it the same path as a word this
     * type does not know, and the two are not the same thing — one is a
     * machine with no device behind it, the other is a bridge that grew a case
     * since this was written. Nothing could tell them apart either: the empty
     * string is not a case here, so every unrecognised word already lands where
     * it lands and swapping one for another changes nothing anybody can
     * observe. That is a line no test can hold, which is what the mutation
     * floor said about it. `WhatTheBridgeSaidTest` drives both.
     */
    public static function orTheDeviceRefused(?string $said): self
    {
        if ($said === null) {
            return self::TheDeviceRefused;
        }

        return self::tryFrom($said) ?? self::TheDeviceRefused;
    }
}
