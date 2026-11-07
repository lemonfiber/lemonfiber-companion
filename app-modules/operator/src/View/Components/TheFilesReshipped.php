<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\AFileReshippedAsShown;

use function view;

/** Each starting file whose placeholders an update changes. */
final class TheFilesReshipped extends Component
{
    /** @param list<AFileReshippedAsShown> $files */
    public function __construct(public readonly array $files) {}

    public function render(): View
    {
        return view('operator::components.the-files-reshipped');
    }
}
