<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * When a plugin's recipe runs: inside an install and an update, or only when asked for by name.
 */
enum WhenARecipeRuns: string
{
    /** As part of installing or updating the plugin. */
    case Install = 'install';

    /** Only when the operator asks for it by name. */
    case Demand = 'demand';
}
