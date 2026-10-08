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

    case IsInFront = 'Lemonfiber.IsInFront';

    /**
     * Ask the device who this is, and wait for the answer.
     *
     * The bridge thread waits while the operator answers, as it does for the
     * camera, and what comes back is what the platform said.
     */
    case Authenticate = 'Lemonfiber.Authenticate';

    /** Whether the app lock stands right now. */
    case LockStanding = 'Lemonfiber.Lock.Standing';

    /** Stand the lock down: the store holds nothing for it to guard. */
    case LockWaive = 'Lemonfiber.Lock.Waive';

    /** The lock screen is on the glass. */
    case LockDrawn = 'Lemonfiber.Lock.Drawn';

    /** How long the app may be away before the lock stands again. */
    case LockAfter = 'Lemonfiber.Lock.After';

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
     * Write one file into the share cache, offer it to whoever the operator
     * picks, and answer whether the sheet was reached.
     *
     * Nothing learns when the chosen app is done reading the file, so it is
     * bounded rather than tracked: one app-private directory used for nothing
     * else, swept before every new handover and on the next launch.
     */
    case OfferFile = 'Lemonfiber.Handover.OfferFile';

    /**
     * Say whether anything is reachable from this device right now.
     *
     * The whole of the capability. What the link is, whether it is metered and
     * whether Low Data Mode is on are all reported by both platforms and none
     * of them is asked for: nothing about the operator's device is reported,
     * and a value held but not sent is one commit away from being sent.
     */
    case LinkStatus = 'Lemonfiber.Link.Status';

    /**
     * Say whether the platform refuses this app the local network on the way to one address.
     *
     * Only the refusal is reported. The platform says why a path cannot be
     * taken, and one reason of them is asked for: that this app has not been
     * allowed onto the local network. Neither the address nor anything about
     * the network goes into the answer or a log line.
     */
    case LocalNetworkProbe = 'Lemonfiber.LocalNetwork.Probe';

    /**
     * Look a machine's name up the way the phone itself does, and answer the
     * addresses the app may send to.
     *
     * The app's runtime does not resolve a `.local` name and the phone does.
     * What comes back is sent to while the name stays the one paired with, and
     * none of it is kept or written to a log line.
     */
    case Resolve = 'Lemonfiber.Resolve';

    /** Open this app's own page in the phone's settings, and answer whether it opened. */
    case SettingsOpen = 'Lemonfiber.Settings.Open';

    case Zone = 'Lemonfiber.Clock.Zone';

    /**
     * The payload of a call that carries nothing.
     *
     * On a handset `nativephp_call` is a C extension that takes exactly two
     * arguments; only the development fallback declares a default for the
     * second, so a call with nothing to carry still hands it this.
     */
    public const string CARRIES_NOTHING = '{}';
}
