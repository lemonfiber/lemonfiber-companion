<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * When a plugin's recipe runs: inside an install and an update, only when asked for by name, or once the part its plugin claims is lemonfiber's.
 */
enum WhenARecipeRuns: string
{
    /** As part of installing or updating the plugin. */
    case Install = 'install';

    /** Only when the operator asks for it by name. */
    case Demand = 'demand';

    /** Once the part its plugin claims is lemonfiber's, under the approvals of the plugin's last install or update, until it has held once. */
    case Claim = 'claim';
}
