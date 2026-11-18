<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

use function view;

/**
 * Why followed work ended before it finished, where the stack said.
 */
final class WhyItEnded extends Component
{
    /** The catalogue key for why it ended, or nothing where the stack did not say. */
    public readonly ?string $said;

    public function __construct(WhereTheWorkStands $stands)
    {
        $this->said = match ($stands->whyItEnded()) {
            WhyTheWorkEnded::Released => 'stacks.why_it_ended.released',
            WhyTheWorkEnded::LetGo => 'stacks.why_it_ended.let_go',
            WhyTheWorkEnded::Quiet => 'stacks.why_it_ended.quiet',
            null => null,
        };
    }

    public function render(): View
    {
        return view('operator::components.why-it-ended');
    }
}
