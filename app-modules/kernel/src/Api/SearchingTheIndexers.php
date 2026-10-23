<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking the indexers about the item a trace followed, which spends a search of their allowance.
 */
enum SearchingTheIndexers: string implements AnAction
{
    case Search = 'search';

    public function asked(): string
    {
        return $this->value;
    }
}
