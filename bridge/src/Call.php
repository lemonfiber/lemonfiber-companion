<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Every bridge function this plugin declares, by name.
 *
 * The names are a vocabulary shared by four files in three languages — this
 * one, `nativephp.json`, `LemonfiberFunctions.kt` and
 * `LemonfiberFunctions.swift` — and three of those are beyond the reach of any
 * PHP rule. A name that disagrees across them is not a compile error anywhere:
 * the bridge answers a function it does not recognise the way it answers a
 * device that is not connected, which decodes here to "the window is not
 * protected". A typo would present as a capture guard that silently does
 * nothing, on a handset, which is the one place nobody is watching a test run.
 *
 * So the PHP side names them once. That does not make the Kotlin and Swift
 * agree — nothing in PHP can — but it removes the copy of the vocabulary most
 * likely to drift, and {@see Tests} holds these against the
 * manifest, which is the file the builder reads when it wires the other two.
 */
enum Call: string
{
    /** A screen holding a secret has come up. */
    case Conceal = 'Lemonfiber.Conceal';

    /** That screen has gone. Backgrounding still protects. */
    case Reveal = 'Lemonfiber.Reveal';

    /** Whether the window is protected from capture right now. */
    case IsProtected = 'Lemonfiber.IsProtected';

    /**
     * Ask the device who this is.
     *
     * Answers immediately and sends the real result as an event: the dialog is
     * the operator's to answer in their own time, and a bridge call that waited
     * would hold the thread it was called on.
     */
    case Authenticate = 'Lemonfiber.Authenticate';

    /**
     * Whether the device can authenticate anybody at all.
     *
     * A device with no screen lock is a different condition from an operator who
     * declined — one is answered by telling them to set one, the other by asking
     * again.
     */
    case CanAuthenticate = 'Lemonfiber.CanAuthenticate';

    /**
     * What the operator has already said about notifications.
     *
     * Reads and never prompts, which is what makes it a separate function from
     * {@see self::Ask}. A capability that cannot read the standing answer
     * without raising a dialog has no way to obey one.
     */
    case Standing = 'Lemonfiber.Telling.Standing';

    /**
     * Raise the notification prompt, and record that it was raised.
     *
     * The recording is the part nothing in Android can otherwise supply: it
     * reports *never asked* and *refused for good* identically, so the
     * difference has to be remembered by whoever raised the dialog.
     */
    case Ask = 'Lemonfiber.Telling.Ask';

    /** Put a notification in front of the operator now, or say why not. */
    case Show = 'Lemonfiber.Telling.Show';

    /** Put one in front of them at a moment, or say why not. */
    case Schedule = 'Lemonfiber.Telling.Schedule';

    /** Put one in front of them over and over, or say why not. */
    case ScheduleRecurring = 'Lemonfiber.Telling.ScheduleRecurring';

    /** Take one back, whether it is showing, scheduled or neither. */
    case Cancel = 'Lemonfiber.Telling.Cancel';

    /** Take back everything this application scheduled. */
    case CancelAll = 'Lemonfiber.Telling.CancelAll';

    /** What is still to come. */
    case Pending = 'Lemonfiber.Telling.Pending';

    /** Take the count off this application's icon. */
    case ClearBadge = 'Lemonfiber.Telling.ClearBadge';

    /**
     * Open the camera and read one pairing code.
     *
     * Answers once the scanner has closed rather than while it is open, which
     * is what lets the code come back in the answer instead of on an event —
     * and an event on one of these platforms is a broadcast into the page.
     */
    case Read = 'Lemonfiber.Scanning.Read';
    /** Put one value in the device's secure store, or say why not. */
    case Keep = 'Lemonfiber.Storage.Keep';

    /**
     * Read one value back, or say there is none, or say nobody could be asked.
     *
     * Named `Kept` here rather than `Read` because the set is a vocabulary and
     * `Read` is already what a camera does. The wire name is what matters and
     * it says `Storage.Read`; this is the PHP word for the same thing.
     */
    case Kept = 'Lemonfiber.Storage.Read';

    /** Take one value out, whether or not it was ever in. */
    case Forget = 'Lemonfiber.Storage.Forget';

    /**
     * Offer a report to whoever the operator picks, and answer whether the
     * sheet was reached.
     *
     * Deliberately not *whether it was sent*: where it went is a choice a
     * person makes in an app this one does not know about, and an app that
     * watched where it went would not be honouring a report assembled for the
     * operator to send rather than sent.
     */
    case Offer = 'Lemonfiber.Handover.Offer';

    /**
     * Say whether anything is reachable from this device right now.
     *
     * The whole of the capability. What the link is, whether it is metered and
     * whether Low Data Mode is on are all reported by both platforms and none
     * of them is asked for: nothing about the operator's device is reported,
     * and a value held but not sent is one commit away from being sent.
     */
    case LinkStatus = 'Lemonfiber.Link.Status';
}
