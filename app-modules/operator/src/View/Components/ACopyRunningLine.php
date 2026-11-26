<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;

use function view;

/** A running backup or restore as one row leading to where it is shown whole: what and of what, how far, and whether it is paced or stalled. */
final class ACopyRunningLine extends Component
{
    /** What is running and of what. */
    public readonly string $title;

    /** How far it has got and how it moves. */
    public readonly string $soFar;

    /** @param string $goes where the row leads */
    public function __construct(Translator $catalogue, public readonly ACopyRunningAsShown $copy, public readonly string $goes)
    {
        $words = new TheCopyRunningInWords($catalogue, $copy);
        $this->title = $words->title();
        $this->soFar = $words->soFar();
    }

    public function render(): View
    {
        return view('operator::components.a-copy-running-line');
    }
}
