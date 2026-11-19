<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\TheWaitAsShown;

use function view;

/**
 * How long to wait before trying to sign in again, where a refusal named it.
 */
final class TheWait extends Component
{
    /** @param TheWaitAsShown|null $wait the wait, or nothing before any refusal */
    public function __construct(public readonly ?TheWaitAsShown $wait) {}

    public function render(): View
    {
        return view('operator::components.the-wait');
    }
}
