<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A page outside the app, opened in the phone's browser.
 */
interface OpeningAPage
{
    /** Open this page in the phone's browser, and say whether it opened. */
    public function open(AnAddressToHand $page): WhetherThePageOpened;
}
