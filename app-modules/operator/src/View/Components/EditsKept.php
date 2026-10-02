<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;

use function view;

/**
 * The stack files the operator edited, which a start or an update left as they set them.
 *
 * One component for the two screens that report them, so a file somebody edited
 * reads the same wherever the stack reports it: kept, never drift.
 */
final class EditsKept extends Component
{
    /** @param list<AnEditAsShown> $edits */
    public function __construct(public readonly array $edits) {}

    public function render(): View
    {
        return view('operator::components.edits-kept');
    }
}
