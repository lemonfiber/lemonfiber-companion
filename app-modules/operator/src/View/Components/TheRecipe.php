<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ViewModels\ARecipeAsShown;

use function view;

/**
 * One recipe as its steps in order, and every value it would carry as its own line with its own switch.
 *
 * One component for an install's recipes and a demand recipe's run, because
 * the rows saying what would leave the machine are the same rows, and two
 * copies of them would come to say it differently.
 */
final class TheRecipe extends Component
{
    use WritesItsTaps;

    public function __construct(public readonly ARecipeAsShown $recipe, public readonly bool $agreeable) {}

    public function render(): View
    {
        return view('operator::components.the-recipe');
    }
}
