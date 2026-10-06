<?php

declare(strict_types=1);

namespace Modules\Household\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

/**
 * The mark every screen of the operator's preview carries, and its way back.
 *
 * It says the screen is a preview and what it is a preview of, and it carries a
 * control back to the operator's screen the preview was opened from, beside
 * the platform's own way back: a member's screens offer nothing else that
 * leads to the operator's.
 */
final class ThePreviewMark extends Component
{
    public function render(): View
    {
        return view('household::components.the-preview-mark');
    }
}
