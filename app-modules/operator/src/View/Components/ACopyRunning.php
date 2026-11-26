<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;

use function view;

/** The backup or restore running on a stack, as its reading says: of what, how much of how much, the part now, when it started, and whether it is paced or stalled. */
final class ACopyRunning extends Component
{
    /** What is running and of what, or that none runs, or that the app cannot tell. */
    public readonly string $title;

    /** How much of how much is written, beside the bar and to a reader of it. */
    public readonly string $written;

    /** Whether it is writing, slowed on purpose or stuck. */
    public readonly string $moves;

    /** How long ago it started. */
    public readonly string $started;

    public function __construct(Translator $catalogue, public readonly ACopyRunningAsShown $copy)
    {
        $words = new TheCopyRunningInWords($catalogue, $copy);
        $this->title = $words->title();
        $this->written = $words->written();
        $this->moves = $copy->moves === '' ? '' : $words->moves();
        $this->started = $copy->started === '' ? '' : $words->started();
    }

    public function render(): View
    {
        return view('operator::components.a-copy-running');
    }
}
