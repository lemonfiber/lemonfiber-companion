<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\APluginAsShown;

use function view;

/**
 * One plugin: what it is, where it came from, and whether anybody reviewed it.
 *
 * One component because a plugin is drawn in two places, its row among what
 * is installed and the head of an install's account, and reviewed or not has
 * to read the same in both. Written twice, one of them would come to leave it
 * out.
 */
final class ThePlugin extends Component
{
    public function __construct(public readonly APluginAsShown $plugin) {}

    public function render(): View
    {
        return view('operator::components.the-plugin');
    }
}
