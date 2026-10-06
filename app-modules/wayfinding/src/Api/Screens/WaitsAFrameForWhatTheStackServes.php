<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Illuminate\View\View;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

use function ob_end_clean;
use function ob_get_level;

use Override;

use function view;

/**
 * A screen that gives the stack a frame of its own to say what it serves.
 *
 * A frame reads a stack once, and a stack this app holds nothing for is asked
 * what it serves before anything is sent: that asking is the frame's one
 * reading, and the reading the frame was drawing for waits
 * ({@see TheReadingWaitsAFrame}). Every screen that reads a stack gets the
 * same frame for it here rather than drawing one of its own: the platform's
 * indicator, and the next frame asked for at once. What the screen was reading
 * was never held, so the next frame reads it as if this one had not.
 *
 * **On the package's own drawing.** A screen's template is drawn by
 * `fromView()`, whichever way the screen answers `render()`, so the frame is
 * caught there, once, for every screen. The first frame a screen draws is also
 * where it opens, and opening after a break asks every stack again
 * ({@see TheWayAround::aScreenOpens()}).
 *
 * **On a tap.** A reading a tap's method takes on a frame that has asked the
 * stack already waits the same way; the tap ends there and the next frame is
 * drawn, rather than the screen being replaced by the package's error.
 *
 * The screen holds the way around in `$around`, which is the coupling, stated
 * here because a trait cannot declare it.
 *
 * @property-read TheWayAround $around
 *
 * @phpstan-require-extends NativeComponent
 */
trait WaitsAFrameForWhatTheStackServes
{
    /** Whether this screen has drawn a frame, which its first frame is where it opens. */
    private bool $hasOpened = false;

    /**
     * Every tap, change and gesture the device sends this screen, whichever way the package routes it.
     *
     * @param array<array-key, mixed> $event
     */
    #[Override]
    protected function dispatchUiEvent(array $event): void
    {
        try {
            parent::dispatchUiEvent($event);
        } catch (TheReadingWaitsAFrame) {
            // The next frame takes the reading; the tap asked for nothing else.
        }
    }

    #[Override]
    protected function fromView(View $view): Element
    {
        if (! $this->hasOpened) {
            $this->hasOpened = true;
            $this->around->aScreenOpens();
        }

        $buffers = ob_get_level();

        try {
            return parent::fromView($view);
        } catch (TheReadingWaitsAFrame) {

            // A component the template was inside when the reading waited
            // left its output buffer open; the frame that waits is drawn
            // with none of them.
            while (ob_get_level() > $buffers) {
                ob_end_clean();
            }

            return parent::fromView(view('wayfinding::components.waiting-for-what-it-serves'));
        }
    }
}
