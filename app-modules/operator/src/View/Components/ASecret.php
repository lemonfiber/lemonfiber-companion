<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ASecretAsShown;

use function view;

/**
 * One secret in the stack's secret store, by its name and never its value.
 *
 * One component for a plugin's secrets and the stack's own, because a secret
 * drawn two ways would come to say two things.
 */
final class ASecret extends Component
{
    public function __construct(public readonly ASecretAsShown $secret) {}

    public function render(): View
    {
        return view('operator::components.a-secret');
    }
}
