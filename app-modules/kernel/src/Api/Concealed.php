<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Attribute;

/**
 * A screen whose frame must never be captured.
 *
 * Three things are named — a credential, a session token, pairing
 * material — and two places a frame is captured without anybody asking: the
 * task-switcher snapshot the platform takes when the app goes to the
 * background, and a screen recording. Neither is an action the operator
 * performs on that screen; both happen to it.
 *
 * **Why an attribute and not a method.** A screen here extends
 * `Native\Mobile\Edge\NativeComponent`, which is somebody else's base class, so
 * there is no abstract method to leave unimplemented and no interface the
 * compiler will ask about. An attribute is the one declaration a rule can
 * insist on for a class the app does not own the shape of — and
 * {@see \Tests\Arch\NothingIsShownInTheTaskSwitcherTest} does insist, for any
 * screen handed material the requirement names.
 *
 * **What this does not do.** Setting `FLAG_SECURE` on Android and covering the
 * window on iOS are native calls, and `nativephp/mobile` exposes neither today
 * — there is no `System::secure()` to reach for. So this is the half that can
 * be true now: every screen that needs the protection *says so*, in a form the
 * shell can read with one reflection pass the day the native side lands. The
 * alternative was a port with no adapter, which `EveryPortIsProvenTwiceTest`
 * would refuse and which would have been a promise rather than a mechanism.
 *
 * The same protection with no exceptions — the task-switcher
 * representation shows no application content at all, on any screen — and is
 * app-wide rather than per-screen, so it belongs to that same native change and
 * is not something a screen declares.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Concealed {}
