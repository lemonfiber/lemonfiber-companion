<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Wayfinding\Api\TheTabs;

use function view;

/**
 * Where else this machine can be read.
 *
 * On every stack-scoped screen, so that moving between the readings of one
 * machine is a property of the app rather than of whichever screen thought to
 * offer a button back. The screen hides the bar where no tab owns it.
 *
 * Navigation is a route rather than a tap handler: the platform owns the
 * selected state and the back gesture, and it can only own them if it is told
 * where each item goes and which tab this screen is under. Told nothing, it
 * lights the first item, so every screen says where it is.
 */
final class ScreenCloses extends Component
{
    public function __construct(
        public readonly WhereAStackIs $goes,
        public readonly ?TheTabs $here,
    ) {}

    public function render(): View
    {
        return view('operator::components.screen-closes');
    }
}
