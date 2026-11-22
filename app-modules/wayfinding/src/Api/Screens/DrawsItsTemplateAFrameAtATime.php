<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * A screen drawing the template its `TEMPLATE` constant names, each frame beginning with nothing read.
 *
 * {@see DrawsItsTemplate} for a screen with more than one reading of its
 * stack, which {@see ReadsAStackOnceAFrame} takes a frame apiece.
 *
 * @phpstan-require-extends NativeComponent
 */
trait DrawsItsTemplateAFrameAtATime
{
    use ReadsAStackOnceAFrame;

    public function render(): View
    {
        $this->aFrameBegins();

        return view(self::TEMPLATE);
    }
}
