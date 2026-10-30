<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use function array_any;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ASettingBeingChanged;
use Modules\Operator\Internal\ViewModels\AChoiceAsShown;
use Modules\Operator\Internal\ViewModels\AStackSettingAsShown;

use function view;

/** Every value a setting lists, each chosen with a tap, and each that is not a choice now with what it needs. */
final class ChoicesOf extends Component
{
    use WritesItsTaps;

    /**
     * @param AStackSettingAsShown $setting the setting, with the values it lists
     * @param string               $chosen  the value chosen for it, blank where none is
     */
    public function __construct(
        public readonly AStackSettingAsShown $setting,
        public readonly string $chosen,
        public readonly ?ASettingBeingChanged $changing,
    ) {}

    /** Whether a value can be chosen: a choice now, or one needing a setting the same change moves, which the stack weighs when it is rehearsed. */
    public function offers(AChoiceAsShown $choice): bool
    {
        $changing = $this->changing;

        return $choice->isOpen || ($changing instanceof ASettingBeingChanged && array_any($choice->hangsOn, $changing->moves(...)));
    }

    public function render(): View
    {
        return view('operator::components.choices-of');
    }
}
