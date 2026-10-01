<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

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
     * Whether the app is in front of somebody right now.
     *
     * Answered from the same lifecycle observer the protection is decided by.
     * Off a handset nobody is looking, and that answer holds nothing open.
     */
    public function isInFront(): bool
    {
        return $this->asked(Call::IsInFront, WhatAnAnswerHolds::InFront);
    }

    /**
     * Whether the device can authenticate anybody at all.
     *
     * False where no screen lock is configured, and false off a handset — which
     * is the honest answer in both cases: there is nobody to ask.
     */
    public function canAuthenticate(): bool
    {
        return $this->asked(Call::CanAuthenticate, WhatAnAnswerHolds::CanAuthenticate);
    }

    /**
     * Ask the device who this is, and answer whether it said so.
     *
     * The reason is shown in the platform's own dialog, which is the app's last
     * chance to say why it is asking. The call waits for the operator, and yes
     * is the platform's success callback and nothing else: a cancel, a failure,
     * a lockout and a device that is not there all answer no.
     */
    public function authenticate(string $reason): bool
    {
        return $this->asked(Call::Authenticate, WhatAnAnswerHolds::Authenticated, ['reason' => $reason]);
    }

    /**
     * Whether the app lock is open right now.
     *
     * Open only on a plain yes. Off a handset, and on any answer that is not
     * one, the lock stands.
     */
    public function lockIsOpen(): bool
    {
        return $this->asked(Call::LockStanding, WhatAnAnswerHolds::Open);
    }

    /** Stand the lock down, and answer whether it is now open. */
    public function waiveTheLock(): bool
    {
        return $this->asked(Call::LockWaive, WhatAnAnswerHolds::Open);
    }

    /**
     * Say the lock screen is on the glass, and answer whether the lock is open.
     *
     * Where the lock may ask by itself, the device raises its prompt with this
     * reason; its answer arrives later, as the lock opening.
     */
    public function lockIsDrawn(string $reason, bool $mayAsk): bool
    {
        return $this->asked(Call::LockDrawn, WhatAnAnswerHolds::Open, ['reason' => $reason, 'ask' => $mayAsk]);
    }

    /**
     * One bridge call, reduced to the one thing every answer carries.
     *
     * {@see WhatTheBridgeAnswered} says what the bridge is on a handset, on a
     * development machine and under test, which is what the tests beside this
     * file drive.
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
     * Yes is `true` and nothing else ({@see WhatTheBridgeAnswered::says()}), and
     * that matters. A `NO_DEVICE` answer decodes to an array with no
     * `protected` key, and a loose check on a missing key is the kind of false
     * that turns into a true the day somebody returns `"false"`. The key is
     * asked for rather than defaulted, which is the same distinction one step
     * earlier: `?? false` cannot say whether the bridge answered no or answered
     * nothing at all (`C9`).
     */
    private function ask(Call $function): bool
    {
        return $this->asked($function, WhatAnAnswerHolds::Protected);
    }

    /**
     * One bridge call, reduced to the one key its answer is about.
     *
     * @param array<string, string|bool> $with
     */
    private function asked(Call $function, WhatAnAnswerHolds $key, array $with = []): bool
    {
        return WhatTheBridgeAnswered::to($function, $with)->says($key);
    }
}
