<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Events;

use Native\Mobile\Events\Concerns\BroadcastsGlobally;

/**
 * The device's lock stood again or opened, so the app reads it afresh.
 *
 * **It carries nothing, and that is the whole of its design.** Whatever arrives
 * as an event is something that could have been sent by anything able to send
 * one, so this says only *look again*: whoever hears it asks the device through
 * the bridge whether the lock stands, and a forged or replayed one can make the
 * app look again and nothing more.
 *
 * Global rather than a screen's own, because the lock is not about the screen
 * on view: whichever screen that is, it is what the lock goes over.
 */
final readonly class TheLockMoved implements BroadcastsGlobally {}
