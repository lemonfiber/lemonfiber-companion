<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function nativephp_call;

/**
 * The window, as the operating system will let this application treat it.
 *
 * The PHP face of lemonfiber's own native expansion. Three calls, each one a
 * message to the Kotlin or Swift beside it; the decision about when the window
 * is protected is made there, in `CaptureRule`, where it can be unit-tested
 * without a handset.
 *
 * **Why this is ours rather than a plugin.** The marketplace sells one that does
 * this, and it was not among the plugins this project holds a licence for. The
 * capability is small — one window flag on Android, one cover view on iOS — and
 * the requirements it serves are not optional, so it is written here rather than
 * bought or deferred.
 *
 * **Off a handset every call reports the window unprotected.** That is honest
 * rather than convenient: a stub claiming otherwise would make a test about
 * screen protection pass on a machine that cannot photograph anything.
 *
 * The seam for testing is `nativephp/mobile`'s own `FakeBridge`, which
 * intercepts `nativephp_call()` in-process. Using it rather than an interface of
 * our own means the tests beside this go through the real call — the method
 * name, the JSON encoding, the decoding of the answer — instead of through a
 * parallel path that only resembles it.
 */
final readonly class Screen
{
    /**
     * Protect the window from capture while a guarded screen is up.
     *
     * On Android this is `FLAG_SECURE`, which refuses screenshots,
     * screen recording and the recents thumbnail alike. On iOS it covers the
     * window while a recording is running and in the task switcher — a
     * deliberate screenshot cannot be blocked there at all, which is a platform
     * limit and is written out in `LemonfiberFunctions.swift`.
     */
    public function conceal(): bool
    {
        return $this->ask(Call::Conceal);
    }

    /**
     * Stop protecting for that screen.
     *
     * Backgrounding still protects afterwards, which is why this
     * answers with the window's resulting state rather than with nothing: on a
     * backgrounded app the answer to "did revealing unprotect the window" is no.
     */
    public function reveal(): bool
    {
        return $this->ask(Call::Reveal);
    }

    /** Whether the window is protected from capture right now. */
    public function isProtected(): bool
    {
        return $this->ask(Call::IsProtected);
    }

    /**
     * Whether the device can authenticate anybody at all.
     *
     * False where no screen lock is configured, and false off a handset — which
     * is the honest answer in both cases: there is nobody to ask.
     */
    public function canAuthenticate(): bool
    {
        return $this->asked(Call::CanAuthenticate, 'canAuthenticate');
    }

    /**
     * Ask the device who this is.
     *
     * The reason is shown in the platform's own dialog, which is the app's last
     * chance to say why it is asking.
     *
     * Answers whether the prompt was *raised*, not whether it succeeded: the
     * dialog is the operator's to answer in their own time, and the real result
     * arrives as an event. A caller wanting the answer waits for that; a caller
     * wanting to know whether anything happened at all reads this.
     */
    public function authenticate(string $reason): bool
    {
        return $this->asked(Call::Authenticate, 'acknowledged', ['reason' => $reason]);
    }

    /**
     * One bridge call, reduced to the one thing every answer carries.
     *
     * `nativephp_call()` is the bridge. On a handset it is a C extension
     * function; on a development machine `nativephp/mobile` supplies a fallback
     * that relays to a connected device — and answers
     * `{"status":"error","code":"NO_DEVICE"}` when there is none. Under test a
     * bound `FakeBridge` intercepts it in-process, which is what the tests
     * beside this file drive.
     *
     * All three of those, plus an unparsable answer, mean the same thing here:
     * this process cannot see a protected window. Collapsing them is right
     * rather than lazy — no caller would do something different for each, and
     * four ways to say "no" is four chances to check only three of them.
     *
     * **There is no `function_exists()` guard, and that is deliberate.** One was
     * written and taken out: `nativephp/mobile` is a hard dependency whose
     * service provider `require_once`s the polyfill during boot, and a `Screen`
     * is only ever obtained from the container — so by the time this line runs,
     * every provider has registered and the function is defined. The guard could
     * not be false in any reachable path, which made it a branch no test could
     * ever enter. `ScreenTest` pins the fact it rested on instead, so the day
     * that stops being true it fails here rather than fataling on a handset.
     *
     * The `=== true` matters. A `NO_DEVICE` answer decodes to an array with no
     * `protected` key, and a loose check on a missing key is the kind of false
     * that turns into a true the day somebody returns `"false"`. The key is
     * asked for rather than defaulted, which is the same distinction one step
     * earlier: `?? false` cannot say whether the bridge answered no or answered
     * nothing at all (`C9`).
     */
    private function ask(Call $function): bool
    {
        return $this->asked($function, 'protected');
    }

    /**
     * One bridge call, reduced to the one key its answer is about.
     *
     * @param array<string, string> $with
     */
    private function asked(Call $function, string $key, array $with = []): bool
    {
        // A payload that will not encode is not sent.
        //
        // `json_encode` answers false for a string that is not valid UTF-8.
        // A `(string)` cast turns that false into `''`, which reaches the
        // device as a call carrying no parameters at all — indistinguishable
        // from one that meant to carry none. The device answers whatever it
        // answers to a call with everything missing, and the operator sees the
        // result of a request nobody made.
        //
        // Refused here instead, as the same false every other way of
        // not reaching the bridge produces: nobody answered, because nobody
        // was asked. It is also the only shape a test can hold: a cast
        // between two values nothing downstream can tell apart is a line
        // nothing can fail on.
        $payload = json_encode($with);

        if (! is_string($payload)) {
            return false;
        }

        $said = json_decode(
            (string) nativephp_call($function->value, $payload),
            associative: true,
        );

        return is_array($said) && array_key_exists($key, $said) && $said[$key] === true;
    }
}
