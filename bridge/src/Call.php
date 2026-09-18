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
}
