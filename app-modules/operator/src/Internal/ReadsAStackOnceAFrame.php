<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Native\Mobile\Edge\NativeComponent;

/**
 * A screen with more than one reading of its stack, taken a frame apiece.
 *
 * A frame reads a stack once and draws everything from what came back. A
 * screen whose frame needs a second reading takes it on the next frame: the
 * reading that waits draws what it can without it, and the frame asks for the
 * next one at once with `<x-operator::the-next-frame />`.
 *
 * The screen's own reading is always taken where it is due. Every other
 * reading asks first, and waits where the frame has read the stack already.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ReadsAStackOnceAFrame
{
    /** Whether the frame being drawn has read the stack. */
    private bool $readThisFrame = false;

    /** Whether a reading waits for the next frame because this one has read the stack. */
    private bool $waitsThisFrame = false;

    /** Whether a reading this frame owed waits for the next one, so the frame asks for it. */
    public function waitsForTheNextFrame(): bool
    {
        return $this->waitsThisFrame;
    }

    /** A frame begins, with nothing read and nothing waiting. Called first thing in `render()`. */
    private function aFrameBegins(): void
    {
        $this->readThisFrame = false;
        $this->waitsThisFrame = false;
    }

    /** The screen's own reading is being taken on this frame. */
    private function readsItsStack(): void
    {
        $this->readThisFrame = true;
    }

    /** Whether this frame may take another reading; taking it is promised by asking. */
    private function mayReadItsStack(): bool
    {
        if ($this->readThisFrame) {
            $this->waitsThisFrame = true;

            return false;
        }

        $this->readThisFrame = true;

        return true;
    }
}
