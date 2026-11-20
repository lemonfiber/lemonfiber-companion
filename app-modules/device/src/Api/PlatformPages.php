<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Pages;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\OpeningAPage;
use Modules\Kernel\Api\WhetherThePageOpened;

/**
 * A page outside the app, opened in the phone's browser through lemonfiber's own call.
 */
final readonly class PlatformPages implements OpeningAPage
{
    public function __construct(private Pages $bridge) {}

    public function open(AnAddressToHand $page): WhetherThePageOpened
    {
        return $this->bridge->open($page->url()) ? WhetherThePageOpened::opened() : WhetherThePageOpened::wouldNot();
    }
}
