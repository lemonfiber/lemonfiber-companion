<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\APluginInstallAsShown;

use function view;

/**
 * An install's account, drawn the same for an install and for the new version an update brings.
 *
 * One component because an update's account holds an install's, the same
 * work, and two copies of the rows that say what would leave the machine
 * would come to say it differently.
 */
final class ThePluginsAccount extends Component
{
    public function __construct(public readonly APluginInstallAsShown $install) {}

    public function render(): View
    {
        return view('operator::components.the-plugins-account');
    }
}
