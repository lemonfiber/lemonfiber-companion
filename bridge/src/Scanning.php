<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * The camera, as this application is willing to use one.
 *
 * The PHP face of lemonfiber's own scanner. One call, which opens the camera,
 * waits for it to close, and answers what it read or why it read nothing. The
 * decisions about whether the camera may be opened at all, and whether anybody
 * may still be asked, are made in `CameraRule` on the device, where they can be
 * unit-tested without a handset.
 *
 * **The code comes back in this answer rather than on an event, deliberately.**
 * An event is the obvious carrier and it is the wrong one here. On Android an
 * event is written to the PHP queue *and*, unless its name begins with `__`,
 * injected into the WebView as JavaScript: it becomes a DOM `CustomEvent` any
 * script on the page can listen for, a `Livewire.dispatch`, and an HTTP POST to
 * `/_native/api/events`. A bridge answer is a JNI string return that touches
 * none of that. Pairing material is the one payload in this application where
 * that difference is a disclosure rather than a detail.
 *
 * **It blocks, and that is what makes the above possible.** The platform's
 * scanner is a screen: it takes the display, and the result is only known once
 * it comes off again. A call that returned immediately would have nothing to
 * put the code in, which is the position the vendor's scanner is in and the
 * reason it has to broadcast. The bridge calls the native half off the main
 * thread, so the camera has a thread to run on while this one waits.
 *
 * **Off a handset it reports the least it can.** Nothing was read and the
 * scanner closed. That is honest rather than convenient: a stand-in claiming a
 * code would make a test about pairing pass on a machine with no camera.
 */
final readonly class Scanning
{
    /** The one word this capability answers a successful read with. */
    private const string READ = 'read';

    /**
     * Open the camera, and answer what it read or why it read nothing.
     *
     * @param string $prompt what the operator is told the camera is for, in
     *                       their own language. Painted over the preview, and
     *                       on the first scan it is the last thing they read
     *                       before the platform's permission dialog.
     */
    public function forAPairingCode(string $prompt): Scanned
    {
        $said = json_decode(
            (string) nativephp_call(Call::Read->value, (string) json_encode(['prompt' => $prompt])),
            associative: true,
        );

        if ($this->wordUnder($said, 'outcome') === self::READ) {
            return Scanned::read($this->wordUnder($said, 'payload') ?? '');
        }

        return Scanned::nothing(
            WhyNothingWasRead::orSimplyDismissed($this->wordUnder($said, 'because')),
            $this->flagUnder($said, 'may_ask_again'),
        );
    }

    /**
     * One word out of an answer, or nothing where there is no word there.
     *
     * Asked for rather than defaulted. `??` on a decoded answer folds absent,
     * present-and-null and present-and-the-wrong-type into one value, and the
     * caller above has a different thing to do about *nobody answered* than
     * about *answered with something unexpected* (`C9`).
     */
    private function wordUnder(mixed $said, string $key): ?string
    {
        if (! is_array($said) || ! array_key_exists($key, $said) || ! is_string($said[$key])) {
            return null;
        }

        return $said[$key];
    }

    /**
     * One flag out of an answer, and false where there is no flag there.
     *
     * False rather than null, because the question it answers has no third
     * state: either asking again could change the answer or it could not, and
     * an answer that did not say is one that could not say. A bridge with no
     * device behind it lands here, and a screen reading *no* offers the typed
     * road — which is the right advice on a machine with no camera.
     */
    private function flagUnder(mixed $said, string $key): bool
    {
        if (! is_array($said) || ! array_key_exists($key, $said) || ! is_bool($said[$key])) {
            return false;
        }

        return $said[$key];
    }
}
