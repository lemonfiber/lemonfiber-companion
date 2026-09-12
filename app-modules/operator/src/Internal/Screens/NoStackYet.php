<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What the operator sees on a launch with nothing paired.
 *
 * `N1-R35` refuses the obvious thing here — an empty operator surface with a
 * button somewhere in it. On a launch with no stack configured the app has to
 * say that setup happens at the machine, and `N1-R4` adds *and why*, rather
 * than omitting it silently. Somebody who installed a companion app reasonably
 * expects to set the thing up from their phone, and a screen that simply offers
 * nothing teaches them the app is broken.
 *
 * **It reaches no port, so it carries no `#[Lazy]`.** That is not an omission:
 * there is nothing configured to reach, so the frame is complete the moment it
 * is built and `F4`'s placeholder would be a spinner in front of a screen that
 * is already finished. Every screen after this one will need the attribute.
 *
 * `Internal` rather than `Api` because nothing outside this module names it —
 * the surface's own provider declares it, and E2's promise is that anything in
 * here can be renamed without reading another module.
 */
final class NoStackYet extends NativeComponent
{
    /**
     * The frame, by name.
     *
     * A `View` rather than an `Element`: the base class accepts either, and a
     * Blade file is the half of a screen `tests/Templates` can read. An element
     * tree assembled in PHP would be invisible to every rule in that suite —
     * `F3`'s vocabulary check, `F5`'s screen-reader check and `L1`'s refusal of
     * an English sentence all work over the text of a template.
     */
    public function render(): View
    {
        return view('operator::no-stack-yet');
    }
}
