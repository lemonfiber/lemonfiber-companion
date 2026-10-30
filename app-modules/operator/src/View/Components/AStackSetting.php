<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Design\Api\WritesItsTaps;
use Modules\Operator\Internal\ASettingBeingChanged;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Modules\Operator\Internal\ViewModels\AStackSettingAsShown;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;

use function view;

/**
 * One setting in the stack's settings store, and changing it or putting it back: rehearsed, agreed to, and followed.
 *
 * One component for a plugin's settings and the stack's own, because a setting
 * changed two ways would come to be changed two ways.
 */
final class AStackSetting extends Component
{
    use WritesItsTaps;

    /**
     * @param ASettingBeingChanged|null     $changing     the setting the screen holds open, or none
     * @param TheSettingChangeTurnedOutToBe $going        where the screen's change has got to
     * @param AnOffer                       $offerToSet   what giving a setting a value is offered as
     * @param AnOffer                       $offerToClear what putting a setting back to its default is offered as
     */
    public function __construct(
        public readonly AStackSettingAsShown $setting,
        public readonly ?ASettingBeingChanged $changing,
        public readonly TheSettingChangeTurnedOutToBe $going,
        public readonly AnOffer $offerToSet,
        public readonly AnOffer $offerToClear,
        public readonly bool $settingsWouldNotOpen,
        public readonly WhereAStackIs $goes,
    ) {}

    public function render(): View
    {
        return view('operator::components.a-stack-setting');
    }
}
