<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Giving back one category of what the disk's offer holds as costing nothing, sent as `space` with the offer's name and that category.
 */
enum GivingBackTheRoom: string implements AnAction
{
    case GiveBack = 'space';

    public function asked(): string
    {
        return $this->value;
    }
}
