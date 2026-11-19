<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AsksWhatSetupSettled;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What setup settled on a stack, as facts about it, to the operator alone. It offers no setup.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatThisStackWasSetUpWith extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use AsksWhatSetupSettled;

    /** What setup settled, once the frame has asked. */
    public ?WhatSetupSettledAsShown $answered = null;

    public function __construct(
        protected readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly AskingWhatSetupSettled $askingSettled,
    ) {}

    /** Let go of the reading, so the next frame takes it again. */
    public function again(): void
    {
        $this->answered = null;
    }

    public function render(): View
    {
        return view('operator::what-this-stack-was-set-up-with');
    }

    /** What setup settled, asked once. */
    public function answer(): WhatSetupSettledAsShown
    {
        return $this->answered ??= $this->whatSetupSettled();
    }
}
